<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\DocumentType;
use App\Models\TaxCondition;
use App\Models\CivilStatus;
use App\Models\Nationality;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'type' => 'individual',
            'name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),

            'document_type_id' => DocumentType::inRandomOrder()->value('id') ?? 1,
            'document_number' => $this->faker->numerify('########'),

            'tax_condition_id' => TaxCondition::inRandomOrder()->value('id') ?? 1,
            'civil_status_id' => CivilStatus::inRandomOrder()->value('id') ?? 1,
            'nationality_id' => Nationality::inRandomOrder()->value('id') ?? 1,
        ];
    }
}
