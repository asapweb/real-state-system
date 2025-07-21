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
            'property_type_id' => PropertyType::factory(), // ✅ Esto es clave
            'street' => $this->faker->streetName(),
            'number' => $this->faker->buildingNumber(),
            'floor' => null,
            'apartment' => null,
            'postal_code' => $this->faker->postcode(),
            'country_id' => 1,
            'state_id' => 1,
            'city_id' => 1,
            'neighborhood_id' => 1,
            'status' => 'draft',
        ];
    }

}
