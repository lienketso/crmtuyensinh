<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStaticApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        // Lấy key từ Header 'X-API-KEY'
        $apiKey = $request->header('X-API-KEY');

        // So sánh với key trong file .env
        if ($apiKey !== config('app.ai_agent_key')) {
            return response()->json([
                'message' => 'Unauthorized: Invalid API Key'
            ], 401);
        }

        return $next($request);
    }
}