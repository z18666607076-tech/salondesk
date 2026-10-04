<?php

namespace Database\Factories;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tenant = Tenant::factory()->create();
        $starts = now()->addDays(3)->setTime(11, 0);

        return [
            'tenant_id' => $tenant->id,
            'customer_id' => Customer::factory()->for($tenant),
            'staff_id' => User::factory()->for($tenant)->bookable(),
            'service_id' => Service::factory()->for($tenant),
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addMinutes(45),
            'status' => AppointmentStatus::Confirmed,
            'source' => AppointmentSource::Api,
            'notes' => null,
        ];
    }
}
