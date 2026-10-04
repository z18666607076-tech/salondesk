<?php

namespace Database\Factories;

use App\Models\StaffSchedule;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StaffSchedule>
 */
class StaffScheduleFactory extends Factory
{
    protected $model = StaffSchedule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tenant = Tenant::factory()->create();

        return [
            'tenant_id' => $tenant->id,
            'user_id' => User::factory()->for($tenant)->bookable(),
            'weekday' => 2,
            'starts_time' => '10:00:00',
            'ends_time' => '19:00:00',
        ];
    }
}
