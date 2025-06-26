<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractAdjustment;
use App\Models\ContractClient;
use App\Models\DocumentType;
use App\Models\TaxCondition;
use App\Models\CivilStatus;
use App\Models\Nationality;
use App\Models\CollectionItem;
use App\Enums\ContractStatus;
use App\Enums\ContractClientRole;
use App\Enums\ContractAdjustmentType;
use App\Enums\CollectionItemType;
use App\Services\CollectionGenerationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Carbon\Carbon;

class IndexAdjustmentGenerationTest extends TestCase
{
    use DatabaseTransactions;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_applies_index_adjustment_to_rent()
    {
        $client = Client::factory()->create([
            'document_type_id' => DocumentType::firstOrCreate(['name' => 'DNI'])->id,
            'tax_condition_id' => TaxCondition::firstOrCreate(['name' => 'Consumidor Final'])->id,
            'civil_status_id' => CivilStatus::firstOrCreate(['name' => 'Soltero'])->id,
            'nationality_id' => Nationality::firstOrCreate(['name' => 'Argentina'])->id,
        ]);

        $contract = Contract::factory()->create([
            'start_date' => '2025-06-01',
            'end_date' => '2026-06-01',
            'monthly_amount' => 100000,
            'currency' => 'ARS',
            'status' => ContractStatus::Active,
        ]);

        ContractClient::factory()->create([
            'contract_id' => $contract->id,
            'client_id' => $client->id,
            'role' => ContractClientRole::TENANT,
        ]);

        // Ajuste de tipo índice del 12% para junio
        ContractAdjustment::create([
            'contract_id' => $contract->id,
            'effective_date' => '2025-06-01',
            'type' => ContractAdjustmentType::Index,
            'value' => 12,
        ]);

        // Ejecutar generación
        $service = new CollectionGenerationService();
        $service->generateForMonth(Carbon::parse('2025-06-01'));

        $collection = $contract->collections()->where('period', '2025-06')->first();
        $this->assertNotNull($collection, 'No se generó la cobranza');

        $item = CollectionItem::where('collection_id', $collection->id)
            ->where('type', CollectionItemType::Rent)
            ->first();

        $this->assertNotNull($item, 'No se generó ítem de alquiler');
        $this->assertEquals(112000, $item->amount, 'El importe ajustado no coincide (debería ser 100000 * 1.12)');

    }
}
