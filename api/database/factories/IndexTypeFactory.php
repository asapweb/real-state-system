<?php

namespace Database\Factories;

use App\Models\IndexType;
use App\Enums\CalculationMode;
use App\Enums\IndexFrequency;
use Illuminate\Database\Eloquent\Factories\Factory;

class IndexTypeFactory extends Factory
{
    protected $model = IndexType::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->randomElement(['ICL', 'UVA', 'CER', 'IPC']),
            'name' => $this->faker->words(3, true),
            'is_active' => true,
            'calculation_mode' => $this->faker->randomElement(CalculationMode::cases()),
            'frequency' => $this->faker->randomElement(IndexFrequency::cases()),
            'is_cumulative' => $this->faker->boolean(70),
        ];
    }
}
