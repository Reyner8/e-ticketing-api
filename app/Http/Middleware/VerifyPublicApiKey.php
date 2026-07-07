<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyPublicApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.public_submission.api_key');

        if (empty($expected)) {
            abort(503, 'Public submission is disabled.');
        }

        $provided = $request->header('X-API-Key') ?? $request->query('api_key');

        if (! is_string($provided) || ! hash_equals((string) $expected, $provided)) {
            abort(401, 'Invalid or missing API key.');
        }

        return $next($request);
    }
}
