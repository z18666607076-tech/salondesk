<?php

use App\Jobs\SendAppointmentConfirmation;
use App\Notifications\AppointmentConfirmed;
use Carbon\CarbonImmutable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-05 09:00:00', 'Asia/Singapore'));
});

it('queues a booking confirmation email', function () {
    Queue::fake();
    $salon = salon();

    $this->withHeaders(tenantHeaders($salon['tenant']))
        ->postJson('/api/v1/bookings', [
            'service_id' => $salon['service']->id,
            'staff_id' => $salon['staff']->id,
            'starts_at' => '2026-10-06T04:00:00+00:00',
            'customer_name' => 'Priya Shah',
            'customer_email' => 'priya@example.com',
        ])
        ->assertCreated();

    Queue::assertPushed(SendAppointmentConfirmation::class);
});

it('sends the confirmation to the customer', function () {
    Notification::fake();
    $salon = salon();

    $this->withHeaders(tenantHeaders($salon['tenant']))
        ->postJson('/api/v1/bookings', [
            'service_id' => $salon['service']->id,
            'staff_id' => $salon['staff']->id,
            'starts_at' => '2026-10-06T04:00:00+00:00',
            'customer_name' => 'Priya Shah',
            'customer_email' => 'priya@example.com',
        ])
        ->assertCreated();

    Notification::assertSentOnDemand(
        AppointmentConfirmed::class,
        function (AppointmentConfirmed $notification, array $channels, object $notifiable): bool {
            return $notifiable->routes['mail'] === ['priya@example.com' => 'Priya Shah']
                && in_array('mail', $channels, true);
        },
    );
});

it('retries the confirmation job with backoff', function () {
    $tries = (new ReflectionClass(SendAppointmentConfirmation::class))
        ->getAttributes(Tries::class)[0]
        ->newInstance();
    $backoff = (new ReflectionClass(SendAppointmentConfirmation::class))
        ->getAttributes(Backoff::class)[0]
        ->newInstance();

    expect($tries->tries)->toBe(3)
        ->and($backoff->backoff)->toBe([10, 30, 60]);
});
