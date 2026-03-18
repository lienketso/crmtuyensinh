<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SchoolController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $query = School::query()->orderBy('name');

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('domain', 'like', "%{$search}%");
        }

        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'contact_email' => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $school = School::create($validator->validated());

        return response()->json($school, 201);
    }

    public function update(Request $request, int $id)
    {
        $school = School::find($id);

        if (! $school) {
            return response()->json(['message' => 'Trường không tồn tại'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'domain' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'contact_email' => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $school->update($validator->validated());

        return response()->json($school);
    }

    public function destroy(int $id)
    {
        $school = School::find($id);

        if (! $school) {
            return response()->json(['message' => 'Trường không tồn tại'], 404);
        }

        $school->delete();

        return response()->json(['message' => 'Đã xoá trường học']);
    }

    /**
     * Danh sách trường (Bearer token).
     * Trả về dạng list đơn giản, không paginate để dễ tích hợp.
     */
    public function list(Request $request)
    {
        $query = School::query()->orderBy('name');

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('domain', 'like', "%{$search}%");
        }

        return response()->json([
            'data' => $query->get(['id', 'name', 'domain', 'contact_email']),
        ]);
    }

    /**
     * Danh sách trường phục vụ tích hợp:
     * - Nếu dùng integration token: trả về tất cả trường.
     * - Nếu user login thường: chỉ trả về trường của user (nếu có school_id).
     */
    public function listForIntegration(Request $request)
    {
        $isIntegration = (bool) $request->attributes->get('is_integration_token', false);
        $user = $request->user();

        $query = School::query()->orderBy('name');

        if (! $isIntegration) {
            // Với user thường, chỉ trả về trường của chính user (nếu có)
            if (! $user || ! $user->school_id) {
                return response()->json(['data' => []]);
            }
            $query->where('id', $user->school_id);
        }

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('domain', 'like', "%{$search}%");
        }

        return response()->json([
            'data' => $query->get(['id', 'name', 'domain', 'contact_email']),
        ]);
    }

    /**
     * Tạo user admin và gán cho một trường.
     */
    public function createAdminForSchool(Request $request, int $schoolId)
    {
        $school = School::find($schoolId);

        if (! $school) {
            return response()->json(['message' => 'Trường không tồn tại'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'nullable|string|min:6|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $plainPassword = $data['password'] ?? bin2hex(random_bytes(4)); // 8 hex chars

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($plainPassword),
            'role' => 'admin',
            'school_id' => $school->id,
        ]);

        return response()->json([
            'message' => 'Đã tạo admin cho trường thành công.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'school_id' => $user->school_id,
            ],
            'plain_password' => $plainPassword,
        ], 201);
    }
}

