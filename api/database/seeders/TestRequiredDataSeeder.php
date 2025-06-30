<?php

namespace Database\Seeders;

// database/seeders/TestRequiredDataSeeder.php
use Illuminate\Database\Seeder;
use App\Models\{PropertyType, Country, State, City, Neighborhood, DocumentType, TaxCondition, CivilStatus, Nationality};

class TestRequiredDataSeeder extends Seeder
{
    public function run(): void
    {
        PropertyType::factory()->create(['id' => 1]);
        Country::factory()->create(['id' => 1]);
        State::factory()->create(['id' => 1, 'country_id' => 1]);
        City::factory()->create(['id' => 1, 'state_id' => 1]);
        Neighborhood::factory()->create(['id' => 1, 'city_id' => 1]);

        DocumentType::factory()->create(['id' => 1]);
        TaxCondition::factory()->create(['id' => 1]);
        CivilStatus::factory()->create(['id' => 1]);
        Nationality::factory()->create(['id' => 1]);
    }
}
