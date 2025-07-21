<?php

namespace Database\Factories;

use App\Models\AfipOperationType;
use Illuminate\Database\Eloquent\Factories\Factory;

class AfipOperationTypeFactory extends Factory
{
    protected $model = AfipOperationType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'afip_id' => $this->faker->numberBetween(1, 99),
            'is_default' => $this->faker->boolean(10), // 10% chance true
        ];
    }
}
