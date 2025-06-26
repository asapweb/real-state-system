<?php

namespace Database\Factories;

use App\Models\Neighborhood;
use Illuminate\Database\Eloquent\Factories\Factory;

class NeighborhoodFactory extends Factory
{
    protected $model = Neighborhood::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->streetName(),
            'city_id' => \App\Models\City::factory(),
            'is_default' => false,
        ];
    }
}
