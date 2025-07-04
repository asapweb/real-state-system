<?php

namespace Tests\Feature;

use App\Enums\CollectionItemType;
use App\Models\Client;
use App\Models\Collection;
use App\Models\CollectionItem;
use App\Models\Contract;
use App\Models\ContractExpense;
use App\Models\DocumentType;
use App\Models\TaxCondition;
use App\Models\CivilStatus;
use App\Models\Nationality;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Neighborhood;
use App\Services\CollectionGenerationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CollectionGenerationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_generates_multiple_collections_per_currency()
    {
        $period = Carbon::create(2025, 7, 1);
        CollectionItem::query()->delete();
        Collection::query()->delete();
        \DB::table('contract_expenses')->delete();
        \DB::table('collection_items')->delete();
        \DB::table('collections')->delete();
        \DB::table('contract_clients')->delete();
        \DB::table('contracts')->delete();


        // Auxiliares para el cliente
        $documentType = DocumentType::factory()->create();
        $taxCondition = TaxCondition::factory()->create();
        $civilStatus = CivilStatus::factory()->create();
        $nationality = Nationality::factory()->create();

        $client = Client::factory()->create([
            'document_type_id' => $documentType->id,
            'tax_condition_id' => $taxCondition->id,
            'civil_status_id' => $civilStatus->id,
            'nationality_id' => $nationality->id,
        ]);

        // Auxiliares para la propiedad
        $propertyType = PropertyType::factory()->create();
        $country = Country::factory()->create();
        $state = State::factory()->create(['country_id' => $country->id]);
        $city = City::factory()->create(['state_id' => $state->id]);
        $neighborhood = Neighborhood::factory()->create(['city_id' => $city->id]);

        $property = Property::factory()->create([
            'property_type_id' => $propertyType->id,
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'neighborhood_id' => $neighborhood->id,
        ]);

        // Contrato con propiedad asociada
        $contract = Contract::factory()->for($property)->create([
            'start_date' => '2025-07-01',
            'end_date' => '2025-07-31',
            'commission_amount' => 1000,
            'currency' => 'ARS',
            'monthly_amount' => 50000,
        ]);

        // Asociar cliente como inquilino
        \App\Models\ContractClient::create([
            'contract_id' => $contract->id,
            'client_id' => $client->id,
            'role' => \App\Enums\ContractClientRole::TENANT,
        ]);

        // Gasto en USD
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

        // Ejecutar generación de cobranzas
        $service = new CollectionGenerationService();
        $service->generateForMonth($period);

        // Verificar 2 cobranzas (ARS y USD)
        $collections = Collection::all();
        $this->assertCount(2, $collections);

        $ars = $collections->firstWhere('currency', 'ARS');
        $usd = $collections->firstWhere('currency', 'USD');

        $this->assertNotNull($ars);
        $this->assertNotNull($usd);

        // Verificar ítems por currency
        $arsItems = CollectionItem::where('collection_id', $ars->id)->get();
        $usdItems = CollectionItem::where('collection_id', $usd->id)->get();

        $this->assertGreaterThan(0, $arsItems->count(), 'ARS items missing');
        $this->assertEquals(1, $usdItems->count(), 'USD should have one item');

        $this->assertEquals('USD', $usdItems->first()->currency);
        $this->assertEquals(CollectionItemType::SERVICE, $usdItems->first()->type);

        // Verificar totales
        $this->assertEquals($arsItems->sum('amount'), $ars->total_amount);
        $this->assertEquals($usdItems->sum('amount'), $usd->total_amount);
    }
}
