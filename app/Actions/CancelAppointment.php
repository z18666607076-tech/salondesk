<?php

namespace App\Actions;

use App\Enums\AppointmentStatus;
use App\Exceptions\CancellationWindowException;
use App\Exceptions\SlotUnavailableException;
use App\Models\Appointment;
use Carbon\CarbonImmutable;

final class CancelAppointment
{
    public function handle(Appointment $appointment, bool $bypassWindow = false): Appointment
    {
        if ($appointment->status !== AppointmentStatus::Confirmed) {
            throw new SlotUnavailableException('Only confirmed appointments can be cancelled.');
        }

        $this->assertWindow($appointment, $bypassWindow);

        $appointment->forceFill([
            'status' => AppointmentStatus::Cancelled,
            'cancelled_at' => now(),
        ])->save();

        return $appointment->refresh();
    }

    public function assertWindow(Appointment $appointment, bool $bypassWindow): void
    {
        $startsAt = CarbonImmutable::parse($appointment->starts_at);

        if ($startsAt->lessThanOrEqualTo(CarbonImmutable::now())) {
            throw new CancellationWindowException('Appointments that have already started cannot be changed.');
        }

        $tenant = $appointment->tenant()->firstOrFail();
        $deadline = CarbonImmutable::now()->addHours((int) $tenant->cancellation_window_hours);

        if (! $bypassWindow && $startsAt->lessThanOrEqualTo($deadline)) {
            throw new CancellationWindowException(
                'This appointment is inside the '.$tenant->cancellation_window_hours.' hour cancellation window.',
            );
        }
    }
}
