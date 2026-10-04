<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('customers.view') || $user->can('customers.manage');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $this->viewAny($user) && $user->tenant_id === $customer->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('customers.manage');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->can('customers.manage') && $user->tenant_id === $customer->tenant_id;
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $this->update($user, $customer);
    }
}
