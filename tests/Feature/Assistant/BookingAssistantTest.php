<?php

use App\Ai\Agents\BookingAgent;
use App\Ai\Contracts\BookingAssistant;
use App\Ai\FakeBookingAssistant;
use App\Ai\LaravelAiBookingAssistant;
use App\Tenancy\TenantContext;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-05 09:00:00', 'Asia/Singapore'));
});

it('proposes a real slot from a natural language request', function () {
    $salon = salon();

    $token = $this->withHeaders(tenantHeaders($salon['tenant']))
        ->postJson('/api/v1/auth/login', [
            'email' => $salon['owner']->email,
            'password' => 'password',
        ])
        ->json('token');

    $this->withHeaders([
        ...tenantHeaders($salon['tenant']),
        'Authorization' => 'Bearer '.$token,
    ])->postJson('/api/v1/assistant/bookings', [
        'message' => 'a haircut with Anna next Tuesday afternoon',
    ])->assertOk()
        ->assertJsonPath('data.attributes.available', true)
        ->assertJsonPath('data.attributes.staff_id', $salon['staff']->id)
        ->assertJsonPath('data.attributes.service_id', $salon['service']->id)
        ->assertJsonPath('data.attributes.starts_at', '2026-10-06T04:00:00+00:00');
});

it('books the proposed slot when asked to confirm', function () {
    $salon = salon();

    $token = $this->withHeaders(tenantHeaders($salon['tenant']))
        ->postJson('/api/v1/auth/login', [
            'email' => $salon['staff']->email,
            'password' => 'password',
        ])
        ->json('token');

    $this->withHeaders([
        ...tenantHeaders($salon['tenant']),
        'Authorization' => 'Bearer '.$token,
    ])->postJson('/api/v1/assistant/bookings', [
        'message' => 'a haircut with Anna next Tuesday afternoon',
        'confirm' => true,
        'customer_name' => 'Priya Shah',
        'customer_email' => 'priya@example.com',
    ])->assertCreated()
        ->assertJsonPath('data.attributes.source', 'ai');
});

it('keeps the assistant off the basic plan', function () {
    $salon = salon(['trial_ends_at' => null]);

    $token = $this->withHeaders(tenantHeaders($salon['tenant']))
        ->postJson('/api/v1/auth/login', [
            'email' => $salon['owner']->email,
            'password' => 'password',
        ])
        ->json('token');

    $this->withHeaders([
        ...tenantHeaders($salon['tenant']),
        'Authorization' => 'Bearer '.$token,
    ])->postJson('/api/v1/assistant/bookings', [
        'message' => 'a haircut with Anna next Tuesday afternoon',
    ])->assertForbidden();
});

it('uses the fake driver when no api key is configured', function () {
    config([
        'booking.assistant_driver' => 'laravel',
        'ai.providers.openai.key' => null,
    ]);

    expect(app(BookingAssistant::class))->toBeInstanceOf(FakeBookingAssistant::class);
});

it('checks a laravel ai proposal against the real schedule', function () {
    $salon = salon();
    app(TenantContext::class)->set($salon['tenant']);

    config([
        'booking.assistant_driver' => 'laravel',
        'ai.providers.openai.key' => 'sk-test',
    ]);

    BookingAgent::fake([[
        'summary' => 'Haircut with Anna Chen.',
        'service_id' => $salon['service']->id,
        'staff_id' => $salon['staff']->id,
        'starts_at' => '2026-10-06T04:00:00+00:00',
    ]]);

    $proposal = app(LaravelAiBookingAssistant::class)->propose('a haircut with Anna next Tuesday afternoon');

    expect($proposal->available)->toBeTrue()
        ->and($proposal->startsAt?->toIso8601String())->toBe('2026-10-06T04:00:00+00:00');

    BookingAgent::fake([[
        'summary' => 'Midnight haircut.',
        'service_id' => $salon['service']->id,
        'staff_id' => $salon['staff']->id,
        'starts_at' => '2026-10-06T16:00:00+00:00',
    ]]);

    $rejected = app(LaravelAiBookingAssistant::class)->propose('midnight');

    expect($rejected->available)->toBeFalse();
});
