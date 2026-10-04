<?php

namespace Database\Seeders;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Models\Customer;
use App\Models\Service;
use App\Models\StaffSchedule;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantRoles;
use App\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->seedGlowStudio();
        $this->seedNorthshoreNails();
    }

    private function seedGlowStudio(): void
    {
        $tenant = Tenant::query()->firstOrCreate(['slug' => 'glow-studio'], [
            'name' => 'Glow Studio',
            'slug' => 'glow-studio',
            'timezone' => 'Asia/Singapore',
            'currency' => 'SGD',
            'locale' => 'en',
            'slot_interval_minutes' => 15,
            'cancellation_window_hours' => 2,
            'billing_email' => 'maya@glow-studio.test',
            'trial_ends_at' => now()->addDays(14),
        ]);

        $tenant->forceFill([
            'timezone' => 'Asia/Singapore',
            'currency' => 'SGD',
            'locale' => 'en',
        ])->save();

        app(TenantContext::class)->set($tenant);
        TenantRoles::ensure($tenant);

        $owner = $this->user($tenant, 'Maya Tan', 'maya@glow-studio.test', false);
        $anna = $this->user($tenant, 'Anna Chen', 'anna@glow-studio.test', true);
        $ben = $this->user($tenant, 'Ben Ong', 'ben@glow-studio.test', true);
        $receptionist = $this->user($tenant, 'Rina Lim', 'rina@glow-studio.test', false);

        TenantRoles::assign($owner, 'owner');
        TenantRoles::assign($anna, 'staff');
        TenantRoles::assign($ben, 'staff');
        TenantRoles::assign($receptionist, 'receptionist');

        $haircut = $this->service($tenant, 'Haircut', 'Cut and finish.', 45, 4800);
        $this->service($tenant, 'Color', 'Single-process color.', 90, 12800);
        $this->service($tenant, 'Blowdry', 'Wash and blowdry.', 30, 3500);

        foreach (range(1, 6) as $weekday) {
            $this->schedule($tenant, $anna, $weekday, '10:00:00', '19:00:00');
        }

        foreach (range(2, 6) as $weekday) {
            $this->schedule($tenant, $ben, $weekday, '12:00:00', '20:00:00');
        }

        $customer = Customer::query()->firstOrCreate(
            ['email' => 'priya@example.com'],
            [
                'name' => 'Priya Shah',
                'phone' => '+65 8123 4567',
            ],
        );

        if ($customer->appointments()->doesntExist()) {
            $starts = CarbonImmutable::now($tenant->timezone)->next(CarbonImmutable::MONDAY)->setTime(10, 0);

            $customer->appointments()->create([
                'staff_id' => $anna->id,
                'service_id' => $haircut->id,
                'starts_at' => $starts->utc(),
                'ends_at' => $starts->addMinutes(45)->utc(),
                'status' => AppointmentStatus::Confirmed,
                'source' => AppointmentSource::Admin,
            ]);
        }

        $guest = Customer::query()->firstOrCreate(
            ['email' => 'wei@example.com'],
            [
                'name' => 'Wei Tan',
                'phone' => '+65 9000 1111',
            ],
        );

        if ($guest->appointments()->doesntExist()) {
            $starts = CarbonImmutable::now($tenant->timezone)->next(CarbonImmutable::TUESDAY)->setTime(14, 0);

            $guest->appointments()->create([
                'staff_id' => $ben->id,
                'service_id' => $haircut->id,
                'starts_at' => $starts->utc(),
                'ends_at' => $starts->addMinutes(45)->utc(),
                'status' => AppointmentStatus::Confirmed,
                'source' => AppointmentSource::Admin,
            ]);
        }
    }

    private function seedNorthshoreNails(): void
    {
        $tenant = Tenant::query()->firstOrCreate(['slug' => 'northshore-nails'], [
            'name' => 'Northshore Nails',
            'slug' => 'northshore-nails',
            'timezone' => 'Asia/Shanghai',
            'currency' => 'CNY',
            'locale' => 'zh_CN',
            'slot_interval_minutes' => 15,
            'cancellation_window_hours' => 2,
            'billing_email' => 'lina@northshore-nails.test',
            'trial_ends_at' => null,
        ]);

        $tenant->forceFill([
            'timezone' => 'Asia/Shanghai',
            'currency' => 'CNY',
            'locale' => 'zh_CN',
            'trial_ends_at' => null,
        ])->save();

        app(TenantContext::class)->set($tenant);
        TenantRoles::ensure($tenant);

        $owner = $this->user($tenant, 'Lina Koh', 'lina@northshore-nails.test', false);
        $noor = $this->user($tenant, 'Noor Idris', 'noor@northshore-nails.test', true);

        TenantRoles::assign($owner, 'owner');
        TenantRoles::assign($noor, 'staff');

        $this->service($tenant, 'Manicure', 'Classic manicure.', 45, 3800);
        $this->service($tenant, 'Pedicure', 'Classic pedicure.', 60, 4800);

        Service::query()->update(['currency' => 'CNY']);

        foreach (range(1, 6) as $weekday) {
            $this->schedule($tenant, $noor, $weekday, '10:00:00', '18:00:00');
        }
    }

    private function user(Tenant $tenant, string $name, string $email, bool $bookable): User
    {
        app(TenantContext::class)->set($tenant);

        return User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => 'password',
                'email_verified_at' => now(),
                'is_bookable' => $bookable,
            ],
        );
    }

    private function service(Tenant $tenant, string $name, string $description, int $duration, int $price): Service
    {
        app(TenantContext::class)->set($tenant);

        return Service::query()->firstOrCreate(
            ['name' => $name],
            [
                'description' => $description,
                'duration_minutes' => $duration,
                'price_cents' => $price,
                'currency' => $tenant->currency,
                'is_active' => true,
            ],
        );
    }

    private function schedule(Tenant $tenant, User $user, int $weekday, string $starts, string $ends): void
    {
        app(TenantContext::class)->set($tenant);

        StaffSchedule::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'weekday' => $weekday,
            ],
            [
                'starts_time' => $starts,
                'ends_time' => $ends,
            ],
        );
    }
}
