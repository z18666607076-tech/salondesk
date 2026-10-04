<?php

use App\Models\Service;
use App\Models\StaffSchedule;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantRoles;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');

/**
 * @param  array<string, mixed>  $tenantAttributes
 * @return array{tenant: Tenant, owner: User, staff: User, service: Service}
 */
function salon(array $tenantAttributes = []): array
{
    $tenant = Tenant::factory()->create($tenantAttributes);

    app(TenantContext::class)->set($tenant);
    TenantRoles::ensure($tenant);

    $owner = User::factory()->for($tenant)->create([
        'name' => 'Maya Tan',
    ]);
    TenantRoles::assign($owner, 'owner');

    $staff = User::factory()->for($tenant)->bookable()->create([
        'name' => 'Anna Chen',
    ]);
    TenantRoles::assign($staff, 'staff');

    $service = Service::factory()->for($tenant)->create([
        'name' => 'Haircut',
        'duration_minutes' => 45,
        'price_cents' => 4800,
        'currency' => 'SGD',
    ]);

    foreach (range(1, 6) as $weekday) {
        StaffSchedule::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $staff->id,
            'weekday' => $weekday,
            'starts_time' => '10:00:00',
            'ends_time' => '19:00:00',
        ]);
    }

    app(TenantContext::class)->set($tenant);

    return compact('tenant', 'owner', 'staff', 'service');
}

/**
 * @return array<string, string>
 */
function tenantHeaders(Tenant $tenant): array
{
    return [
        'X-Tenant' => $tenant->slug,
        'Accept' => 'application/json',
    ];
}
