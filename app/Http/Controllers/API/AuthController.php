<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
class AuthController extends Controller
{
    /**
     * Đăng nhập
     */
    public function login(Request $request)
    {
        Log::info('Login attempt:', $request->all());

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            Log::warning('User not found:', ['email' => $request->email]);
            throw ValidationException::withMessages([
                'email' => ['Thông tin đăng nhập không chính xác.'],
            ]);
        }

        Log::info('User found:', ['id' => $user->id, 'email' => $user->email]);

        // Kiểm tra mật khẩu
        if (!Hash::check($request->password, $user->password)) {
            Log::warning('Password incorrect for user:', ['email' => $request->email]);
            throw ValidationException::withMessages([
                'email' => ['Thông tin đăng nhập không chính xác.'],
            ]);
        }

        Log::info('Password correct, creating token...');

        // Tạo token
        $token = $user->createToken('auth_token')->plainTextToken;

        Log::info('Token created for user:', ['user_id' => $user->id]);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at,
            ]
        ], 200); // Thêm status code rõ ràng
    }

    /**
     * Đăng xuất
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Đăng xuất thành công']);
    }

    /**
     * Lấy thông tin user hiện tại
     */
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Cập nhật thông tin cá nhân (name/email)
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->fill([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
        ]);
        $user->save();

        return response()->json([
            'message' => 'Cập nhật thông tin cá nhân thành công.',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Đổi mật khẩu (yêu cầu current_password)
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!Hash::check($request->input('current_password'), (string) $user->password)) {
            return response()->json([
                'message' => 'Mật khẩu hiện tại không đúng.',
                'errors' => [
                    'current_password' => ['Mật khẩu hiện tại không đúng.'],
                ],
            ], 422);
        }

        // Tránh đổi sang đúng mật khẩu cũ
        if (Hash::check($request->input('password'), (string) $user->password)) {
            return response()->json([
                'message' => 'Mật khẩu mới phải khác mật khẩu hiện tại.',
                'errors' => [
                    'password' => ['Mật khẩu mới phải khác mật khẩu hiện tại.'],
                ],
            ], 422);
        }

        $user->password = Hash::make($request->input('password'));
        $user->save();

        return response()->json([
            'message' => 'Đổi mật khẩu thành công.',
        ]);
    }
}
