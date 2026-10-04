<?php

namespace App\Policies;

use App\Models\StaffSchedule;
use App\Models\User;

class StaffSchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('schedules.manage');
    }

    public function view(User $user, StaffSchedule $schedule): bool
    {
        return $this->update($user, $schedule);
    }

    public function create(User $user): bool
    {
        return $user->can('schedules.manage');
    }

    public function update(User $user, StaffSchedule $schedule): bool
    {
        if (! $user->can('schedules.manage') || $user->tenant_id !== $schedule->tenant_id) {
            return false;
        }

        return $user->hasRole('owner') || $schedule->user_id === $user->id;
    }

    public function delete(User $user, StaffSchedule $schedule): bool
    {
        return $this->update($user, $schedule);
    }
}
