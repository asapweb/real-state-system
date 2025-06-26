<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Neighborhood;
use App\Models\PropertyType;
use App\Enums\PropertyStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        return [
            'property_type_id' => PropertyType::inRandomOrder()->value('id') ?? 1,

            'street' => $this->faker->streetName(),
            'number' => $this->faker->buildingNumber(),
            'floor' => null,
            'apartment' => null,
            'postal_code' => $this->faker->postcode(),

            'country_id' => Country::inRandomOrder()->value('id') ?? 1,
            'state_id' => State::inRandomOrder()->value('id') ?? 1,
            'city_id' => City::inRandomOrder()->value('id') ?? 1,
            'neighborhood_id' => Neighborhood::inRandomOrder()->value('id') ?? 1,

            'status' => PropertyStatus::Draft,
        ];
    }
}
