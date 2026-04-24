<?php

namespace Database\Factories;

use App\Domain\Services\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'price' => fake()->randomFloat(2, 5, 500),
            'disabled_at' => null,
            'required_product_id' => null,
        ];
    }

    public function disabled(): static
    {
        return $this->state(['disabled_at' => now()]);
    }
}
