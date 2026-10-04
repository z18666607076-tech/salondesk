<?php

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\User;
use App\Support\TenantRoles;
use Carbon\CarbonImmutable;

beforeEach(function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-05 09:00:00', 'Asia/Singapore'));
    $this->travelTo(CarbonImmutable::now());
});

it('lets a receptionist see every stylist and bypass the cancellation window', function () {
    $salon = salon();
    $ben = User::factory()->for($salon['tenant'])->bookable()->create(['name' => 'Ben Ong']);
    TenantRoles::assign($ben, 'staff');
    $receptionist = User::factory()->for($salon['tenant'])->create([
        'name' => 'Rina Lim',
        'is_bookable' => false,
    ]);
    TenantRoles::assign($receptionist, 'receptionist');

    $priya = Customer::factory()->for($salon['tenant'])->create([
        'name' => 'Priya Shah',
        'email' => 'priya@example.com',
    ]);
    $wei = Customer::factory()->for($salon['tenant'])->create([
        'name' => 'Wei Tan',
        'email' => 'wei@example.com',
    ]);

    Appointment::query()->create([
        'customer_id' => $priya->id,
        'staff_id' => $salon['staff']->id,
        'service_id' => $salon['service']->id,
        'starts_at' => CarbonImmutable::parse('2026-10-06T04:00:00+00:00'),
        'ends_at' => CarbonImmutable::parse('2026-10-06T04:45:00+00:00'),
        'status' => AppointmentStatus::Confirmed,
        'source' => AppointmentSource::Admin,
    ]);
    Appointment::query()->create([
        'customer_id' => $wei->id,
        'staff_id' => $ben->id,
        'service_id' => $salon['service']->id,
        'starts_at' => CarbonImmutable::parse('2026-10-06T06:00:00+00:00'),
        'ends_at' => CarbonImmutable::parse('2026-10-06T06:45:00+00:00'),
        'status' => AppointmentStatus::Confirmed,
        'source' => AppointmentSource::Admin,
    ]);

    $soon = Appointment::query()->create([
        'customer_id' => $priya->id,
        'staff_id' => $salon['staff']->id,
        'service_id' => $salon['service']->id,
        'starts_at' => CarbonImmutable::parse('2026-10-05T02:00:00+00:00'),
        'ends_at' => CarbonImmutable::parse('2026-10-05T02:45:00+00:00'),
        'status' => AppointmentStatus::Confirmed,
        'source' => AppointmentSource::Admin,
    ]);

    $desk = $this->withHeaders(tenantHeaders($salon['tenant']))
        ->postJson('/api/v1/auth/login', [
            'email' => $receptionist->email,
            'password' => 'password',
        ])
        ->assertOk()
        ->json('token');

    $stylist = $this->withHeaders(tenantHeaders($salon['tenant']))
        ->postJson('/api/v1/auth/login', [
            'email' => $salon['staff']->email,
            'password' => 'password',
        ])
        ->assertOk()
        ->json('token');

    $this->withHeaders([
        ...tenantHeaders($salon['tenant']),
        'Authorization' => 'Bearer '.$desk,
    ])->getJson('/api/v1/appointments')
        ->assertOk()
        ->assertJsonCount(3, 'data');

    $this->withHeaders([
        ...tenantHeaders($salon['tenant']),
        'Authorization' => 'Bearer '.$stylist,
    ])->getJson('/api/v1/appointments')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $this->withHeaders([
        ...tenantHeaders($salon['tenant']),
        'Authorization' => 'Bearer '.$stylist,
    ])->postJson('/api/v1/appointments/'.$soon->id.'/cancel')
        ->assertStatus(422);

    $this->withHeaders([
        ...tenantHeaders($salon['tenant']),
        'Authorization' => 'Bearer '.$desk,
    ])->postJson('/api/v1/appointments/'.$soon->id.'/cancel')
        ->assertOk()
        ->assertJsonPath('data.attributes.status', AppointmentStatus::Cancelled->value);

    $this->flushHeaders();

    $this->actingAs($receptionist, 'web')
        ->get('/admin/'.$salon['tenant']->slug.'/calendar?view=day&date=2026-10-06')
        ->assertOk()
        ->assertSee('Priya Shah')
        ->assertSee('Wei Tan')
        ->assertSee('Anna Chen')
        ->assertSee('Ben Ong');

    $this->actingAs($salon['staff'], 'web')
        ->get('/admin/'.$salon['tenant']->slug.'/calendar?view=week&date=2026-10-06')
        ->assertOk()
        ->assertSee('Priya Shah')
        ->assertDontSee('Wei Tan')
        ->assertDontSee('Ben Ong');

    $this->actingAs($receptionist, 'web')
        ->get('/admin/'.$salon['tenant']->slug.'/manage-billing')
        ->assertForbidden();
});
