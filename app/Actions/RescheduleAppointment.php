<?php

namespace App\Actions;

use App\Booking\CalculateAvailability;
use App\Enums\AppointmentStatus;
use App\Exceptions\SlotUnavailableException;
use App\Jobs\SendAppointmentConfirmation;
use App\Models\Appointment;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class RescheduleAppointment
{
    public function __construct(
        private CalculateAvailability $availability,
        private CancelAppointment $cancellations,
    ) {}

    public function handle(Appointment $appointment, CarbonImmutable $startsAt, bool $bypassWindow = false): Appointment
    {
        return DB::transaction(function () use ($appointment, $startsAt, $bypassWindow): Appointment {
            $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);

            if ($appointment->status !== AppointmentStatus::Confirmed) {
                throw new SlotUnavailableException('Only confirmed appointments can be rescheduled.');
            }

            $this->cancellations->assertWindow($appointment, $bypassWindow);

            $service = $appointment->service()->firstOrFail();
            $staff = User::query()->withoutGlobalScope(TenantScope::class)->lockForUpdate()->findOrFail($appointment->staff_id);
            $slot = $this->availability->findSlot($service, $staff, $startsAt, $appointment->id);

            if ($slot === null) {
                throw new SlotUnavailableException('That time is not an open slot for this staff member.');
            }

            $appointment->forceFill([
                'starts_at' => $slot->startsAt,
                'ends_at' => $slot->endsAt,
            ])->save();

            SendAppointmentConfirmation::dispatch($appointment->id);

            return $appointment->refresh()->load(['customer', 'staff', 'service']);
        });
    }
}
