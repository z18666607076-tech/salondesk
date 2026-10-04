<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('appointments.view');
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->tenant_id !== $appointment->tenant_id || ! $user->can('appointments.view')) {
            return false;
        }

        return $user->seesEveryAppointment() || $appointment->staff_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('appointments.manage');
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $this->manage($user, $appointment);
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->hasRole('owner') && $user->tenant_id === $appointment->tenant_id;
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        return $this->manage($user, $appointment);
    }

    public function reschedule(User $user, Appointment $appointment): bool
    {
        return $this->manage($user, $appointment);
    }

    private function manage(User $user, Appointment $appointment): bool
    {
        if ($user->tenant_id !== $appointment->tenant_id || ! $user->can('appointments.manage')) {
            return false;
        }

        return $user->seesEveryAppointment() || $appointment->staff_id === $user->id;
    }
}
