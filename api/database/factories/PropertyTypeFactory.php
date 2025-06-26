<?php

namespace Database\Factories;

use App\Models\PropertyType;
use Illuminate\Database\Eloquent\Factories\Factory;

class PropertyTypeFactory extends Factory
{
    protected $model = PropertyType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'is_default' => false,
        ];
    }
}
