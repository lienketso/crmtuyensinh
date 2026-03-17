<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AdmissionProfile;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

class AdmissionProfileController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            $query = AdmissionProfile::with('lead')->latest();

            if ($user && ! $user->isSuperAdmin()) {
                // Chỉ xem hồ sơ của lead thuộc trường của mình
                $query->whereHas('lead', function ($q) use ($user) {
                    $q->where('school_id', $user->school_id);
                });
            }

            $profiles = $query->get();

            return response()->json([
                'success' => true,
                'data' => $profiles // Bọc trong object 'data'
            ]);
        } catch (\Exception $e) {
            // Trả về lỗi chi tiết để debug thay vì chỉ hiện 500
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    // Chuyển đổi Lead thành Hồ sơ chính thức (gọi từ API static key)
    public function create(Request $request, $leadId)
    {
        $lead = Lead::findOrFail($leadId);

        $profile = AdmissionProfile::create([
            'lead_id' => $lead->id,
            'identification_number' => $request->identification_number,
            // Khi tạo hồ sơ từ hệ thống external coi như đã đăng ký xét tuyển
            'document_status' => 'registered',
            'academic_records' => $request->academic_records, // Dạng JSON
        ]);

        $lead->update(['status' => 'considering']);

        return response()->json([
            'message' => 'Đã tạo hồ sơ xét tuyển thành công',
            'profile' => $profile
        ]);
    }

    /**
     * Tạo hồ sơ xét tuyển từ lead (CRM - user đăng nhập).
     */
    public function createFromLead(Request $request, $leadId)
    {
        $user = $request->user();
        $lead = Lead::findOrFail($leadId);

        if ($user && ! $user->isSuperAdmin() && $lead->school_id !== $user->school_id) {
            return response()->json(['message' => 'Lead không tồn tại.'], 404);
        }

        if (AdmissionProfile::where('lead_id', $lead->id)->exists()) {
            return response()->json([
                'message' => 'Lead này đã có hồ sơ xét tuyển.',
            ], 422);
        }

        $profile = AdmissionProfile::create([
            'lead_id' => $lead->id,
            'identification_number' => $request->input('identification_number'),
            // Hồ sơ tạo từ CRM: trạng thái mặc định "Đã đăng ký xét tuyển"
            'document_status' => 'registered',
            'academic_records' => $request->input('academic_records', []),
        ]);

        $lead->update(['status' => 'considering']);

        return response()->json([
            'message' => 'Đã tạo hồ sơ xét tuyển thành công.',
            'profile' => $profile->load('lead'),
        ], 201);
    }

    // Cập nhật điểm số (Có thể gọi từ kết quả OCR của AI)
    public function updateAcademicRecords(Request $request, $id)
    {
        $profile = AdmissionProfile::findOrFail($id);
        
        // Validate điểm số từ request
        $profile->update([
            'academic_records' => $request->input('scores'),
            'is_verified' => false // Đợi cán bộ check lại
        ]);

        return response()->json(['message' => 'Cập nhật điểm thành công']);
    }

    // Cán bộ duyệt hồ sơ
    public function verify($id)
    {
        $profile = AdmissionProfile::findOrFail($id);
        // Duyệt hồ sơ: chuyển sang trạng thái "Hồ sơ hợp lệ"
        $profile->update(['document_status' => 'valid']);

        return response()->json(['message' => 'Hồ sơ đã được xác thực']);
    }

    /**
     * Chi tiết một hồ sơ (CRM - auth).
     */
    public function show(int $id)
    {
        $profile = AdmissionProfile::with('lead')->findOrFail($id);
        $user = request()->user();

        if ($user && ! $user->isSuperAdmin()) {
            if (! $profile->lead || $profile->lead->school_id !== $user->school_id) {
                return response()->json(['message' => 'Hồ sơ không tồn tại.'], 404);
            }
        }
        $profile->admission_file_url = $profile->admission_file
            ? URL::asset('storage/' . ltrim($profile->admission_file, '/'))
            : null;

        return response()->json($profile);
    }

    /**
     * Cập nhật hồ sơ (CRM - auth), hỗ trợ upload file.
     */
    public function update(Request $request, int $id)
    {
        $profile = AdmissionProfile::with('lead')->findOrFail($id);
        $user = $request->user();

        if ($user && ! $user->isSuperAdmin()) {
            if (! $profile->lead || $profile->lead->school_id !== $user->school_id) {
                return response()->json(['message' => 'Hồ sơ không tồn tại.'], 404);
            }
        }

        $validator = Validator::make($request->all(), [
            'identification_number' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'graduation_year' => 'nullable|integer|min:1990|max:2100',
            'academic_level' => 'nullable|string|max:50',
            'gpa' => 'nullable|numeric|min:0|max:10',
            'admission_method' => 'nullable|string|max:100',
            'document_status' => 'nullable|in:not_registered,registered,submitted,need_more_docs,valid,in_review,admitted,confirmed,enrolled,pending,verified,rejected',
            'admin_note' => 'nullable|string|max:2000',
            'academic_records' => 'nullable|string', // JSON string from FormData
            'admission_file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $path = null;
        if ($request->hasFile('admission_file')) {
            if ($profile->admission_file) {
                Storage::disk('public')->delete($profile->admission_file);
            }
            $path = $request->file('admission_file')->store('admission-files', 'public');
        }
        unset($data['admission_file']);

        if (isset($data['academic_records']) && is_string($data['academic_records'])) {
            $decoded = json_decode($data['academic_records'], true);
            $data['academic_records'] = is_array($decoded) ? $decoded : null;
        }

        $updateData = array_filter($data, fn ($v) => $v !== null);
        if ($path !== null) {
            $updateData['admission_file'] = $path;
        }
        $profile->update($updateData);

        $profile->load('lead');
        $profile->admission_file_url = $profile->admission_file
            ? URL::asset('storage/' . ltrim($profile->admission_file, '/'))
            : null;

        return response()->json([
            'message' => 'Đã cập nhật hồ sơ.',
            'profile' => $profile,
        ]);
    }
}