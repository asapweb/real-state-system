<?php

namespace Database\Seeders;

use App\Models\{
    Client, Contract, ContractClient, ContractExpense, Country,
    State, City, Neighborhood, Property, PropertyType,
    DocumentType, TaxCondition, CivilStatus, Nationality
};
use App\Enums\ContractClientRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ContractBenchmarkSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar tablas relacionadas
        DB::table('contract_expenses')->delete();
        DB::table('contract_clients')->delete();
        DB::table('contracts')->delete();
        DB::table('clients')->delete();
        DB::table('properties')->delete();

        // Datos comunes
        $documentType = DocumentType::factory()->create();
        $taxCondition = TaxCondition::factory()->create();
        $civilStatus = CivilStatus::factory()->create();
        $nationality = Nationality::factory()->create();

        $propertyType = PropertyType::factory()->create();
        $country = Country::factory()->create();
        $state = State::factory()->create(['country_id' => $country->id]);
        $city = City::factory()->create(['state_id' => $state->id]);
        $neighborhood = Neighborhood::factory()->create(['city_id' => $city->id]);

        foreach (range(1, 500) as $i) {
            $client = Client::factory()->create([
                'document_type_id' => $documentType->id,
                'tax_condition_id' => $taxCondition->id,
                'civil_status_id' => $civilStatus->id,
                'nationality_id' => $nationality->id,
            ]);

            $property = Property::factory()->create([
                'property_type_id' => $propertyType->id,
                'country_id' => $country->id,
                'state_id' => $state->id,
                'city_id' => $city->id,
                'neighborhood_id' => $neighborhood->id,
            ]);

            $contract = Contract::factory()->for($property)->create([
                'start_date' => '2025-07-01',
                'end_date' => '2026-07-01',
                'monthly_amount' => 50000,
                'currency' => 'ARS',
                'commission_amount' => 1000,
                'insurance_required' => true,
                'insurance_amount' => 2000,
            ]);

            ContractClient::create([
                'contract_id' => $contract->id,
                'client_id' => $client->id,
                'role' => ContractClientRole::TENANT,
            ]);

            // Gasto en USD que genera cobranza adicional
            ContractExpense::factory()->create([
                'contract_id' => $contract->id,
                'service_type' => 'expensas',
                'amount' => 100,
                'currency' => 'USD',
                'paid_by' => 'agency',
                'is_paid' => true,
                'included_in_collection' => false,
                'period' => '2025-07-01',
            ]);
        }
    }
}
