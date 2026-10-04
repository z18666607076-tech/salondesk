<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('services.view') || $user->can('services.manage');
    }

    public function view(User $user, Service $service): bool
    {
        return $this->viewAny($user) && $user->tenant_id === $service->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('services.manage');
    }

    public function update(User $user, Service $service): bool
    {
        return $user->can('services.manage') && $user->tenant_id === $service->tenant_id;
    }

    public function delete(User $user, Service $service): bool
    {
        return $this->update($user, $service);
    }
}
