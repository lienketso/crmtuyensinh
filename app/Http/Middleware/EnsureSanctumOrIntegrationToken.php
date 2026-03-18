<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cho phép truy cập API theo 2 cách:
 * - Bearer token từ Sanctum (user login bình thường)
 * - Hoặc 1 Bearer token "mặc định" cấu hình trong ENV (không cần login)
 *   Khi dùng token mặc định, middleware sẽ gán request user là user cấu hình (thường là super admin).
 */
class EnsureSanctumOrIntegrationToken
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1) Ưu tiên user từ sanctum nếu có
        $sanctumUser = Auth::guard('sanctum')->user();
        if ($sanctumUser) {
            Auth::setUser($sanctumUser);
            return $next($request);
        }

        // 2) Fallback: token tích hợp (không cần login)
        $integrationToken = (string) config('app.integration_bearer_token', '');
        if ($integrationToken !== '') {
            $bearer = (string) $request->bearerToken();

            if ($bearer !== '' && hash_equals($integrationToken, $bearer)) {
                // Đánh dấu request này đang dùng integration token
                $request->attributes->set('is_integration_token', true);

                $email = (string) config('app.integration_user_email', 'superadmin@example.com');
                $user = User::where('email', $email)->first();

                if (! $user) {
                    return response()->json([
                        'message' => 'Integration user not found.',
                    ], 500);
                }

                Auth::setUser($user);
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'Unauthenticated.',
        ], 401);
    }
}

