<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\User;
use App\Support\TenantRoles;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-05 09:00:00', 'Asia/Singapore'));
    $this->travelTo(CarbonImmutable::now());
});

it('returns afternoon slots inside working hours', function () {
    $salon = salon();

    $response = $this->withHeaders(tenantHeaders($salon['tenant']))
        ->getJson('/api/v1/availability?service_id='.$salon['service']->id.'&date=2026-10-06&staff_id='.$salon['staff']->id.'&time_of_day=afternoon');

    $response->assertOk();

    $starts = collect($response->json('data'))->pluck('attributes.starts_at');

    expect($starts->first())->toBe('2026-10-06T04:00:00+00:00')
        ->and($starts->contains('2026-10-06T01:45:00+00:00'))->toBeFalse();
});

it('books a slot and rejects a second booking for the same time', function () {
    Notification::fake();
    $salon = salon();
    $payload = [
        'service_id' => $salon['service']->id,
        'staff_id' => $salon['staff']->id,
        'starts_at' => '2026-10-06T04:00:00+00:00',
        'customer_name' => 'Priya Shah',
        'customer_email' => 'priya@example.com',
    ];

    $this->withHeaders(tenantHeaders($salon['tenant']))
        ->postJson('/api/v1/bookings', $payload)
        ->assertCreated()
        ->assertJsonPath('data.attributes.service_name', 'Haircut')
        ->assertJsonPath('data.attributes.staff_name', 'Anna Chen')
        ->assertJsonPath('data.attributes.status', 'confirmed');

    $this->withHeaders(tenantHeaders($salon['tenant']))
        ->postJson('/api/v1/bookings', [
            ...$payload,
            'customer_email' => 'other@example.com',
            'customer_name' => 'Other Guest',
        ])
        ->assertStatus(422);

    expect(Appointment::query()->count())->toBe(1);
});

it('cancels outside the window and rejects a late cancellation', function () {
    Notification::fake();
    $salon = salon(['cancellation_window_hours' => 2]);

    $created = $this->withHeaders(tenantHeaders($salon['tenant']))
        ->postJson('/api/v1/bookings', [
            'service_id' => $salon['service']->id,
            'staff_id' => $salon['staff']->id,
            'starts_at' => '2026-10-08T02:00:00+00:00',
            'customer_name' => 'Priya Shah',
            'customer_email' => 'priya@example.com',
        ])
        ->assertCreated();

    $id = $created->json('data.id');

    $this->withHeaders(tenantHeaders($salon['tenant']))
        ->postJson('/api/v1/bookings/'.$id.'/cancel', ['email' => 'wrong@example.com'])
        ->assertStatus(422);

    $this->withHeaders(tenantHeaders($salon['tenant']))
        ->postJson('/api/v1/bookings/'.$id.'/cancel', ['email' => 'priya@example.com'])
        ->assertOk()
        ->assertJsonPath('data.attributes.status', 'cancelled');

    $late = $this->withHeaders(tenantHeaders($salon['tenant']))
        ->postJson('/api/v1/bookings', [
            'service_id' => $salon['service']->id,
            'staff_id' => $salon['staff']->id,
            'starts_at' => '2026-10-05T03:00:00+00:00',
            'customer_name' => 'Priya Shah',
            'customer_email' => 'priya@example.com',
        ]);

    $salon['tenant']->forceFill(['cancellation_window_hours' => 48])->save();

    if ($late->status() === 201) {
        $this->withHeaders(tenantHeaders($salon['tenant']))
            ->postJson('/api/v1/bookings/'.$late->json('data.id').'/cancel', ['email' => 'priya@example.com'])
            ->assertStatus(422);
    } else {
        expect($late->status())->toBe(422);
    }
});

it('reschedules onto a free slot and frees the original one', function () {
    Notification::fake();
    $salon = salon();

    $created = $this->withHeaders(tenantHeaders($salon['tenant']))
        ->postJson('/api/v1/bookings', [
            'service_id' => $salon['service']->id,
            'staff_id' => $salon['staff']->id,
            'starts_at' => '2026-10-06T04:00:00+00:00',
            'customer_name' => 'Priya Shah',
            'customer_email' => 'priya@example.com',
        ])
        ->assertCreated();

    $this->withHeaders(tenantHeaders($salon['tenant']))
        ->postJson('/api/v1/bookings/'.$created->json('data.id').'/reschedule', [
            'email' => 'priya@example.com',
            'starts_at' => '2026-10-06T06:00:00+00:00',
        ])
        ->assertOk()
        ->assertJsonPath('data.attributes.starts_at', '2026-10-06T06:00:00+00:00');

    $this->withHeaders(tenantHeaders($salon['tenant']))
        ->postJson('/api/v1/bookings', [
            'service_id' => $salon['service']->id,
            'staff_id' => $salon['staff']->id,
            'starts_at' => '2026-10-06T04:00:00+00:00',
            'customer_name' => 'Amina Yusuf',
            'customer_email' => 'amina@example.com',
        ])
        ->assertCreated();
});

it('lets the assigned stylist cancel and hides the visit from other staff', function () {
    Notification::fake();
    $salon = salon();
    $other = User::factory()->for($salon['tenant'])->bookable()->create(['name' => 'Ben Ong']);
    TenantRoles::assign($other, 'staff');

    $created = $this->withHeaders(tenantHeaders($salon['tenant']))
        ->postJson('/api/v1/bookings', [
            'service_id' => $salon['service']->id,
            'staff_id' => $salon['staff']->id,
            'starts_at' => '2026-10-08T04:00:00+00:00',
            'customer_name' => 'Priya Shah',
            'customer_email' => 'priya@example.com',
        ])
        ->assertCreated();

    $token = $this->withHeaders(tenantHeaders($salon['tenant']))
        ->postJson('/api/v1/auth/login', [
            'email' => $other->email,
            'password' => 'password',
        ])
        ->json('token');

    $this->withHeaders([
        ...tenantHeaders($salon['tenant']),
        'Authorization' => 'Bearer '.$token,
    ])->getJson('/api/v1/appointments')
        ->assertOk()
        ->assertJsonCount(0, 'data');

    $anna = $this->withHeaders(tenantHeaders($salon['tenant']))
        ->postJson('/api/v1/auth/login', [
            'email' => $salon['staff']->email,
            'password' => 'password',
        ])
        ->json('token');

    $this->withHeaders([
        ...tenantHeaders($salon['tenant']),
        'Authorization' => 'Bearer '.$anna,
    ])->postJson('/api/v1/appointments/'.$created->json('data.id').'/cancel')
        ->assertOk()
        ->assertJsonPath('data.attributes.status', AppointmentStatus::Cancelled->value);
});

it('stops a basic plan from adding a fourth staff member', function () {
    $tenant = salon(['trial_ends_at' => null])['tenant'];

    User::factory()->for($tenant)->create();

    expect(fn () => User::factory()->for($tenant)->create())
        ->toThrow(ValidationException::class);
});
