<?php

namespace App\Http\Middleware;

use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserBelongsToTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $tenant = app(TenantContext::class)->get();

        if ($user !== null && $tenant !== null && $user->tenant_id !== $tenant->id) {
            return response()->json([
                'message' => 'You do not belong to this salon.',
            ], 403);
        }

        return $next($request);
    }
}
