<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\Collection;
use App\Models\CollectionItem;
use App\Models\Contract;
use App\Models\ContractClient;
use App\Models\PropertyType;
use App\Models\DocumentType;
use App\Models\TaxCondition;
use App\Models\CivilStatus;
use App\Models\Nationality;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Neighborhood;
use App\Enums\ContractStatus;
use App\Enums\ContractClientRole;
use App\Services\CollectionGenerationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Carbon\Carbon;
use App\Enums\CollectionItemType;

class CollectionGenerationTestOld extends TestCase
{
    use DatabaseTransactions;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_generates_monthly_collection_for_active_contract()
    {
        // Datos base requeridos
        $documentType = DocumentType::firstOrCreate(['name' => 'DNI']);
        $taxCondition = TaxCondition::firstOrCreate(['name' => 'Consumidor Final']);
        $civilStatus = CivilStatus::firstOrCreate(['name' => 'Soltero']);
        $nationality = Nationality::firstOrCreate(['name' => 'Argentina']);
        $country = Country::firstOrCreate(['name' => 'Argentina']);
        $state = State::firstOrCreate(['name' => 'Buenos Aires', 'country_id' => $country->id]);
        $city = City::firstOrCreate(['name' => 'Ciudad Autónoma de Buenos Aires', 'state_id' => $state->id]);
        $neighborhood = Neighborhood::firstOrCreate(['name' => 'Centro', 'city_id' => $city->id]);
        $propertyType = PropertyType::firstOrCreate(['name' => 'Departamento']);

        // Creamos cliente
        $client = Client::factory()->create([
            'document_type_id' => $documentType->id,
            'tax_condition_id' => $taxCondition->id,
            'civil_status_id' => $civilStatus->id,
            'nationality_id' => $nationality->id,
        ]);

        // Creamos contrato
        $contract = Contract::factory()->create([
            'start_date' => '2025-06-01',
            'end_date' => '2026-05-31',
            'monthly_amount' => 100000,
            'currency' => 'ARS',
            'status' => ContractStatus::Active,
        ]);

        // Asociamos cliente como inquilino y aseguramos consistencia

        ContractClient::factory()->create([
            'contract_id' => $contract->id,
            'client_id' => $client->id,
            'role' => ContractClientRole::TENANT,
        ]);

        // Aseguramos que el contrato tenga el cliente precargado
        // $contract->setRelation('clients', collect([$client]));

        // Ejecutamos la generación de cobranzas
        $service = new CollectionGenerationService();
        $service->generateForMonth(Carbon::create(2025, 6, 1));

        // Verificamos que se haya generado una cobranza
        $collection = Collection::where('contract_id', $contract->id)->first();
        $this->assertNotNull($collection, 'La cobranza no fue generada');
        $this->assertEquals('pending', $collection->status);
        $this->assertEquals('ARS', $collection->currency);

        // Verificamos que exista ítem de alquiler
        $item = CollectionItem::where('collection_id', $collection->id)->first();
        $this->assertNotNull($item, 'No se encontró item asociado');
        $this->assertEquals(CollectionItemType::Rent, $item->type);
        $this->assertEquals(100000, $item->amount);
    }
}
