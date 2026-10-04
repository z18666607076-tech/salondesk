<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\RequestGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * The Sanctum request guard caches the user for the process lifetime.
 * HTTP tests and long-lived workers send a new bearer token per request.
 */
class ResetSanctumGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('sanctum');

        if ($guard instanceof RequestGuard) {
            $guard->forgetUser();
        }

        return $next($request);
    }
}
