<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('staff.view') || $user->can('staff.manage');
    }

    public function view(User $user, User $model): bool
    {
        return $this->viewAny($user) && $user->tenant_id === $model->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('staff.manage');
    }

    public function update(User $user, User $model): bool
    {
        if ($user->tenant_id !== $model->tenant_id) {
            return false;
        }

        return $user->can('staff.manage') || $user->is($model);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can('staff.manage')
            && $user->tenant_id === $model->tenant_id
            && ! $user->is($model);
    }
}
