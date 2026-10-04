<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    public function manageBilling(User $user, Tenant $tenant): bool
    {
        return $user->hasRole('owner')
            && $user->can('billing.manage')
            && $user->tenant_id === $tenant->id;
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $user->hasRole('owner') && $user->tenant_id === $tenant->id;
    }
}
