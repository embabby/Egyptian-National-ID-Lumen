<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuthMiddleware
{
    /**
     * Handle an incoming request. Require valid API key via X-API-Key or Authorization: Bearer.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-API-Key')
            ?? $this->bearerToken($request);

        if (empty($key)) {
            return response()->json([
                'success' => false,
                'message' => 'Missing or invalid API key. Provide X-API-Key header or Authorization: Bearer <key>.',
            ], 401);
        }

        $apiKey = ApiKey::where('key_hash', hash('sha256', $key))->where('is_active', true)->first();

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or inactive API key.',
            ], 401);
        }

        $request->attributes->set('api_key_entity', $apiKey);

        return $next($request);
    }

    private function bearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization');
        if (!is_string($header) || !str_starts_with($header, 'Bearer ')) {
            return null;
        }

        return trim(substr($header, 7));
    }
}
