<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'timezone' => 'Asia/Singapore',
            'currency' => 'SGD',
            'locale' => 'en',
            'slot_interval_minutes' => 15,
            'cancellation_window_hours' => 2,
            'billing_email' => fake()->companyEmail(),
            'trial_ends_at' => now()->addDays(14),
        ];
    }

    public function basic(): static
    {
        return $this->state(fn (): array => [
            'trial_ends_at' => null,
        ]);
    }
}
