<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    /**
     * Lấy danh sách courses
     */
    public function index(Request $request)
    {
        $courses = Course::orderBy('created_at', 'desc')->get();

        return response()->json($courses);
    }

    public function getCourse(Request $request)
    {
        $courses = Course::orderBy('created_at', 'desc')->get();
        return response()->json($courses);
    }
    /**
     * Tạo course mới
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration' => 'nullable|string|max:100',
            'tuition_fee' => 'nullable|numeric|min:0',
            'target_student' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        // Chỉ admin mới được tạo course
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Không có quyền thực hiện'], 403);
        }

        $course = Course::create([
            'name' => $request->name,
            'description' => $request->description,
            'duration' => $request->duration,
            'tuition_fee' => $request->tuition_fee,
            'target_student' => $request->target_student,
        ]);

        return response()->json($course, 201);
    }

    /**
     * Cập nhật course
     */
    public function update(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'duration' => 'nullable|string|max:100',
            'tuition_fee' => 'nullable|numeric|min:0',
            'target_student' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        // Chỉ admin mới được cập nhật course
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Không có quyền thực hiện'], 403);
        }

        $course = Course::where('id', $id)
            ->first();

        if (!$course) {
            return response()->json(['message' => 'Course không tồn tại'], 404);
        }

        $course->update($request->only([
            'name', 'description', 'duration', 'tuition_fee', 'target_student'
        ]));

        return response()->json($course);
    }

    /**
     * Xóa course
     */
    public function destroy(Request $request, int $id)
    {
        // Chỉ admin mới được xóa course
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Không có quyền thực hiện'], 403);
        }

        $course = Course::where('id', $id)
            ->first();

        if (!$course) {
            return response()->json(['message' => 'Course không tồn tại'], 404);
        }

        $course->delete();

        return response()->json(['message' => 'Xóa thành công']);
    }
}
