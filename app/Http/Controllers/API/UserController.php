<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $current = $request->user();

        $q = trim((string) $request->query('q', ''));
        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min($perPage, 200));

        $users = User::query()
            ->when(! $current?->isSuperAdmin(), function ($query) use ($current) {
                // Admin/advisor: chỉ xem user cùng trường, không thấy super_admin
                $query->where('school_id', $current->school_id)
                      ->where('role', '!=', 'super_admin');
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($q2) use ($q) {
                    $q2->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->latest('id')
            ->paginate($perPage);

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $current = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', Rule::in(['super_admin', 'admin', 'advisor'])],
            'school_id' => ['nullable', 'integer', 'exists:schools,id'],
        ]);

        // Admin thường chỉ được tạo user trong trường của mình, không tạo super_admin
        if (! $current->isSuperAdmin()) {
            $data['role'] = in_array($data['role'], ['admin', 'advisor'], true) ? $data['role'] : 'advisor';
            $data['school_id'] = $current->school_id;
        }

        $user = User::create($data);

        return response()->json($user, 201);
    }

    public function show(User $user)
    {
        $current = request()->user();
        if ($current && ! $current->isSuperAdmin()) {
            if ($user->role === 'super_admin' || $user->school_id !== $current->school_id) {
                return response()->json(['message' => 'Người dùng không tồn tại.'], 404);
            }
        }

        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $current = $request->user();

        if ($current && ! $current->isSuperAdmin()) {
            if ($user->role === 'super_admin' || $user->school_id !== $current->school_id) {
                return response()->json(['message' => 'Người dùng không tồn tại.'], 404);
            }
        }

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['sometimes', 'required', Rule::in(['super_admin', 'admin', 'advisor'])],
            'school_id' => ['nullable', 'integer', 'exists:schools,id'],
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        if ($current && ! $current->isSuperAdmin()) {
            // Admin thường không được đổi trường và không được gán super_admin
            unset($data['school_id']);
            if (isset($data['role']) && $data['role'] === 'super_admin') {
                unset($data['role']);
            }
        }

        $user->update($data);

        return response()->json($user);
    }

    public function destroy(User $user)
    {
        $current = request()->user();

        if ($current && ! $current->isSuperAdmin()) {
            if ($user->role === 'super_admin' || $user->school_id !== $current->school_id) {
                return response()->json(['message' => 'Người dùng không tồn tại.'], 404);
            }
        }

        $user->delete();

        return response()->json(['message' => 'Xóa người dùng thành công.']);
    }
}

