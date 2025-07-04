<?php

namespace Tests\Unit;

use App\Enums\ContractClientRole;
use App\Enums\ContractStatus;
use App\Models\City;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractAdjustment;
use App\Models\ContractClient;
use App\Models\Country;
use App\Models\Neighborhood;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\State;
use App\Services\CollectionGenerationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContractAdjustmentProratedTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_applies_prorated_adjustment_and_marks_as_applied(): void
    {
        $period = Carbon::parse('2025-07');

        $property = Property::factory()->create([
            'property_type_id' => 1,
            'country_id' => 1,
            'state_id' => 2,
            'city_id' => 1,
            'neighborhood_id' => 1,
        ]);

        $contract = Contract::factory()->create([
            'property_id' => $property->id,
            'start_date' => '2025-07-15',
            'end_date' => '2025-12-31',
            'monthly_amount' => 100000,
            'prorate_first_month' => true,
            'status' => ContractStatus::ACTIVE,
        ]);

        $client = Client::factory()->create();

        ContractClient::factory()->create([
            'contract_id' => $contract->id,
            'client_id' => $client->id,
            'role' => ContractClientRole::TENANT,
            'is_primary' => true,
        ]);

        $adjustment = ContractAdjustment::create([
    'contract_id' => $contract->id,
    'effective_date' => $period->copy()->subMonth()->startOfMonth(),
    'type' => 'percentage',
    'value' => 50, // este valor debe estar en porcentaje, o sea, 50 significa +50%
]);

        $service = new CollectionGenerationService();
        $service->generateForMonth($period);

        $collection = $contract->collections()->where('period', '2025-07')->first();
        $this->assertNotNull($collection, 'La cobranza de julio no fue generada');

        $rentItem = $collection->items->firstWhere('type', 'rent');

        $this->assertNotNull($rentItem, 'No se generó el item de alquiler');

        $this->assertEquals(82258.06, $rentItem->amount);
        $this->assertNotNull($adjustment->fresh()->applied_at, 'El campo applied_at no fue seteado en el ajuste');

        $this->assertEquals($adjustment->id, $rentItem->meta['adjustment_id']);
        $this->assertDatabaseHas('contract_adjustments', [
            'id' => $adjustment->id,
        ]);
    }
}
