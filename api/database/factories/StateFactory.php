<?php

namespace Database\Factories;

use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

class StateFactory extends Factory
{
    protected $model = State::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->state(),
            'country_id' => \App\Models\Country::factory(),
            'is_default' => false,
        ];
    }
}
