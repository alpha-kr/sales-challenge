<?php

namespace Database\Factories;

use App\Domain\Clients\Models\Client;
use App\Domain\Sales\Models\Sale;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'daily_sequence' => 1,
            'total' => fake()->randomFloat(2, 10, 9999),
            'created_at' => now(),
        ];
    }

    public function onDate(Carbon|string $date): static
    {
        return $this->state(['created_at' => $date]);
    }
}
