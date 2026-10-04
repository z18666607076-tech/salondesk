<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\Scopes\TenantScope;
use App\Notifications\AppointmentConfirmed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Notification;

#[Tries(3)]
#[Backoff(10, 30, 60)]
class SendAppointmentConfirmation implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $appointmentId) {}

    public function handle(): void
    {
        $appointment = Appointment::query()
            ->withoutGlobalScope(TenantScope::class)
            ->with(['customer', 'service', 'staff', 'tenant'])
            ->find($this->appointmentId);

        if ($appointment === null || blank($appointment->customer->email)) {
            return;
        }

        Notification::route('mail', [
            $appointment->customer->email => $appointment->customer->name,
        ])->notify(new AppointmentConfirmed($appointment));
    }
}
