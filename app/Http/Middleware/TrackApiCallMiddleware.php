<?php

namespace App\Http\Middleware;

use App\Models\ApiCall;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackApiCallMiddleware
{
    /**
     * Record the API call for billing/tracking (runs after route; we need to log in terminate or after response).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $this->track($request, $response);

        return $response;
    }

    private function track(Request $request, Response $response): void
    {
        $apiKey = $request->attributes->get('api_key_entity');
        if (!$apiKey) {
            return;
        }

        try {
            ApiCall::create([
                'api_key_id' => $apiKey->id,
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'national_id_requested' => $request->input('national_id') ?? $request->query('national_id'),
                'response_status' => $response->getStatusCode(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
