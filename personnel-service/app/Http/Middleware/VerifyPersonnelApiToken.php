<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyPersonnelApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = config('services.personnel_api_token');
        $providedToken = $request->bearerToken();

        if (
            ! is_string($configuredToken)
            || $configuredToken === ''
            || ! is_string($providedToken)
            || $providedToken === ''
            || ! hash_equals($configuredToken, $providedToken)
        ) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
