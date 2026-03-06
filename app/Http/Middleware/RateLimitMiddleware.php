<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    /**
     * Limit requests per API key (e.g. 60 per minute). Uses cache for sliding window.
     */
    public function handle(Request $request, Closure $next, string $maxAttempts = '60', string $decayMinutes = '1'): Response
    {
        $signature = $this->resolveRequestSignature($request);
        $maxAttempts = (int) $maxAttempts;
        $decaySeconds = (int) $decayMinutes * 60;
        $cacheKey = 'rate_limit:' . $signature;

        $attempts = (int) Cache::get($cacheKey, 0);

        if ($attempts >= $maxAttempts) {
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
            ], 429);
        }

        if ($attempts === 0) {
            Cache::put($cacheKey, 1, $decaySeconds);
        } else {
            Cache::increment($cacheKey);
        }

        $response = $next($request);

        $currentAttempts = (int) Cache::get($cacheKey, 0);
        $response->headers->set('X-RateLimit-Limit', (string) $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', (string) max(0, $maxAttempts - $currentAttempts));

        return $response;
    }

    private function resolveRequestSignature(Request $request): string
    {
        $apiKey = $request->attributes->get('api_key_entity');
        if ($apiKey) {
            return 'national_id:api_key:' . $apiKey->id;
        }

        return 'national_id:ip:' . $request->ip();
    }
}
