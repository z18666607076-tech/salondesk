<?php

use App\Models\Scopes\TenantScope;
use App\Models\Service;
use App\Tenancy\TenantContext;

it('rejects api requests that do not identify a tenant', function () {
    $this->getJson('/api/v1/services')->assertStatus(400);
});

it('does not list another salon services', function () {
    $glow = salon();
    $nails = salon();

    $this->withHeaders(tenantHeaders($glow['tenant']))
        ->getJson('/api/v1/services')
        ->assertOk()
        ->assertJsonFragment(['name' => 'Haircut'])
        ->assertJsonMissing(['id' => (string) $nails['service']->id]);

    $this->withHeaders(tenantHeaders($glow['tenant']))
        ->getJson('/api/v1/services/'.$nails['service']->id)
        ->assertNotFound();
});

it('hides another salon service by id on the availability endpoint', function () {
    $glow = salon();
    $nails = salon();

    $this->withHeaders(tenantHeaders($glow['tenant']))
        ->getJson('/api/v1/availability?service_id='.$nails['service']->id.'&date=2026-10-06')
        ->assertNotFound();
});

it('does not let a staff token read another salon', function () {
    $glow = salon();
    $nails = salon();

    $token = $this->withHeaders(tenantHeaders($glow['tenant']))
        ->postJson('/api/v1/auth/login', [
            'email' => $glow['owner']->email,
            'password' => 'password',
        ])
        ->assertOk()
        ->json('token');

    $this->withHeaders([
        ...tenantHeaders($nails['tenant']),
        'Authorization' => 'Bearer '.$token,
    ])->getJson('/api/v1/appointments')->assertForbidden();
});

it('scopes eloquent queries to the current tenant', function () {
    $glow = salon();
    $nails = salon();

    app(TenantContext::class)->set($glow['tenant']);

    expect(Service::query()->pluck('id')->all())->toBe([$glow['service']->id])
        ->and(Service::query()->withoutGlobalScope(TenantScope::class)->count())->toBe(2);

    app(TenantContext::class)->set($nails['tenant']);

    expect(Service::query()->pluck('id')->all())->toBe([$nails['service']->id]);
});

it('resolves a tenant from its subdomain', function () {
    $glow = salon(['slug' => 'glow-studio']);

    $this->getJson('http://glow-studio.salondesk.test/api/v1/services')
        ->assertOk()
        ->assertJsonFragment(['name' => 'Haircut'])
        ->assertJsonPath('data.0.id', (string) $glow['service']->id);
});
