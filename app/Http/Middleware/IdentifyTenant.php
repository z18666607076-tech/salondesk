<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $this->slugFromHost($request) ?? $request->header((string) config('tenancy.header'));

        if (! is_string($slug) || $slug === '') {
            return response()->json([
                'message' => 'Tenant could not be identified. Send the X-Tenant header or use a tenant subdomain.',
            ], 400);
        }

        $tenant = Tenant::query()->where('slug', $slug)->first();

        if ($tenant === null) {
            return response()->json([
                'message' => 'Unknown tenant.',
            ], 404);
        }

        app(TenantContext::class)->set($tenant);

        return $next($request);
    }

    private function slugFromHost(Request $request): ?string
    {
        $baseDomain = (string) config('tenancy.base_domain');
        $host = $request->getHost();

        if ($baseDomain === '' || ! str_ends_with($host, '.'.$baseDomain)) {
            return null;
        }

        $slug = Str::beforeLast($host, '.'.$baseDomain);

        if ($slug === '' || str_contains($slug, '.')) {
            return null;
        }

        return $slug;
    }
}
