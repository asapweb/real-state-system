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
        $country = \App\Models\Country::firstOrCreate(['name' => 'Argentina']);
        $state = \App\Models\State::firstOrCreate(['name' => 'Buenos Aires'], ['country_id' => $country->id]);
        $city = \App\Models\City::firstOrCreate(['name' => 'CABA'], ['state_id' => $state->id]);
        $neighborhood = \App\Models\Neighborhood::firstOrCreate(['name' => 'Centro'], ['city_id' => $city->id]);

        $propertyType = \App\Models\PropertyType::firstOrCreate(['name' => 'default'], ['is_default' => true]);

        $documentType = \App\Models\DocumentType::firstOrCreate(['name' => 'DNI']);
        $taxCondition = \App\Models\TaxCondition::firstOrCreate(['name' => 'Consumidor Final']);
        $civilStatus = \App\Models\CivilStatus::firstOrCreate(['name' => 'Soltero']);
        $nationality = \App\Models\Nationality::firstOrCreate(['name' => 'Argentina']);

        $this->reference = compact(
            'country', 'state', 'city', 'neighborhood',
            'propertyType', 'documentType', 'taxCondition',
            'civilStatus', 'nationality'
        );
    }


    protected function createValidContract(array $overrides = []): Contract
    {
        $this->ensureReferenceData();

        $tenant = Client::factory()->create([
            'type' => 'individual',
            'document_type_id' => $this->reference['documentType']->id,
            'tax_condition_id' => $this->reference['taxCondition']->id,
            'civil_status_id' => $this->reference['civilStatus']->id,
            'nationality_id' => $this->reference['nationality']->id,
        ]);

        $property = Property::factory()->create([
            'property_type_id' => $this->reference['propertyType']->id,
            'country_id' => $this->reference['country']->id,
            'state_id' => $this->reference['state']->id,
            'city_id' => $this->reference['city']->id,
            'neighborhood_id' => $this->reference['neighborhood']->id,
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
