<?php

namespace App\Models;

use App\Enums\Plan;
use App\Models\Scopes\TenantScope;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Cashier\Billable;

#[Fillable([
    'name',
    'slug',
    'timezone',
    'currency',
    'locale',
    'slot_interval_minutes',
    'cancellation_window_hours',
    'billing_email',
])]
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use Billable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'slot_interval_minutes' => 'integer',
            'cancellation_window_hours' => 'integer',
            'trial_ends_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Customer, $this>
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * @return HasMany<Service, $this>
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * @return HasMany<StaffSchedule, $this>
     */
    public function staffSchedules(): HasMany
    {
        return $this->hasMany(StaffSchedule::class);
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function resolvedPlan(): Plan
    {
        $subscription = $this->subscription('default');

        if ($subscription !== null && $subscription->valid()) {
            return Plan::fromStripePrice($subscription->stripe_price);
        }

        if ($this->onGenericTrial()) {
            return Plan::Pro;
        }

        return Plan::Basic;
    }

    public function canAddStaff(): bool
    {
        $maxStaff = $this->resolvedPlan()->maxStaff();

        if ($maxStaff === null) {
            return true;
        }

        $count = User::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->id)
            ->count();

        return $count < $maxStaff;
    }

    public function stripeName(): string
    {
        return $this->name;
    }

    public function stripeEmail(): ?string
    {
        return $this->billing_email;
    }
}
