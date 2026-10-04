<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'is_bookable'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use BelongsToTenant, HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            $tenantId = $user->getAttribute('tenant_id');

            if ($tenantId === null) {
                return;
            }

            $tenant = Tenant::query()->find($tenantId);

            if ($tenant !== null && ! $tenant->canAddStaff()) {
                throw ValidationException::withMessages([
                    'name' => 'The current plan does not allow another staff member. Upgrade to Pro to add more.',
                ]);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_bookable' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->getAttribute('tenant_id') !== null;
    }

    /**
     * @return Collection<int, Tenant>
     */
    public function getTenants(Panel $panel): Collection
    {
        return $this->tenant === null
            ? new Collection
            : new Collection([$this->tenant]);
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->tenant_id === $tenant->getKey();
    }

    public function seesEveryAppointment(): bool
    {
        return $this->hasRole('owner') || $this->hasRole('receptionist');
    }

    /**
     * @return HasMany<StaffSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(StaffSchedule::class);
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'staff_id');
    }
}
