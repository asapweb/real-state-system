<?php

namespace Tests\Traits;

use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractClient;
use App\Models\Property;
use Illuminate\Support\Carbon;

trait CreatesValidContract
{
    protected function ensureReferenceData(): void
    {
        \App\Models\PropertyType::updateOrCreate(['id' => 1], ['name' => 'default', 'is_default' => true]);
        \App\Models\Country::updateOrCreate(['id' => 1], ['name' => 'Argentina']);
        \App\Models\State::updateOrCreate(['id' => 1, 'country_id' => 1], ['name' => 'Buenos Aires']);
        \App\Models\City::updateOrCreate(['id' => 1, 'state_id' => 1], ['name' => 'CABA']);
        \App\Models\Neighborhood::updateOrCreate(['id' => 1, 'city_id' => 1], ['name' => 'Centro']);

        \App\Models\DocumentType::updateOrCreate(['id' => 1], ['name' => 'DNI']);
        \App\Models\TaxCondition::updateOrCreate(['id' => 1], ['name' => 'Consumidor Final']);
        \App\Models\CivilStatus::updateOrCreate(['id' => 1], ['name' => 'Soltero']);
        \App\Models\Nationality::updateOrCreate(['id' => 1], ['name' => 'Argentina']);
    }

    protected function createValidContract(array $overrides = []): Contract
    {
        $this->ensureReferenceData();

        $tenant = Client::factory()->create([
            'type' => 'individual',
            'document_type_id' => 1,
            'tax_condition_id' => 1,
            'civil_status_id' => 1,
            'nationality_id' => 1,
        ]);

        $property = Property::factory()->create([
            'property_type_id' => 1,
            'country_id' => 1,
            'state_id' => 1,
            'city_id' => 1,
            'neighborhood_id' => 1,
        ]);

        $contract = Contract::factory()->create(array_merge([
            'property_id' => $property->id,
            'start_date' => Carbon::now()->subMonths(1)->startOfMonth(),
            'end_date' => Carbon::now()->addMonths(11)->endOfMonth(),
            'currency' => 'ARS',
        ], $overrides));

        ContractClient::create([
            'contract_id' => $contract->id,
            'client_id' => $tenant->id,
            'role' => 'tenant',
            'is_primary' => true,
        ]);

        return $contract->fresh(['clients']);
    }
}
