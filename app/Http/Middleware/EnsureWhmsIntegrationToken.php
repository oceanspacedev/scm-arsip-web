<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Melindungi endpoint integrasi WHMS (server ke server) dengan token rahasia bersama.
 */
class EnsureWhmsIntegrationToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.whms.token');
        $given = (string) $request->bearerToken();

        if ($expected === '' || $given === '' || ! hash_equals($expected, $given)) {
            return response()->json([
                'success' => false,
                'message' => 'Token integrasi WHMS tidak valid.',
            ], 401);
        }

        return $next($request);
    }
}
