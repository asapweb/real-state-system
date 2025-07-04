<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractClient;
use App\Models\DocumentType;
use App\Models\TaxCondition;
use App\Models\CivilStatus;
use App\Models\Nationality;
use App\Models\Collection;
use App\Models\CollectionItem;
use App\Enums\ContractStatus;
use App\Enums\ContractClientRole;
use App\Enums\CollectionItemType;
use App\Enums\CommissionPayer;
use App\Enums\CommissionType;
use App\Services\CollectionGenerationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Carbon\Carbon;

class CommissionGenerationTest extends TestCase
{
    use DatabaseTransactions;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_generates_commission_item_on_first_month_if_required()
    {
        // Crear cliente válido
        $client = Client::factory()->create([
            'document_type_id' => DocumentType::firstOrCreate(['name' => 'DNI'])->id,
            'tax_condition_id' => TaxCondition::firstOrCreate(['name' => 'Consumidor Final'])->id,
            'civil_status_id' => CivilStatus::firstOrCreate(['name' => 'Soltero'])->id,
            'nationality_id' => Nationality::firstOrCreate(['name' => 'Argentina'])->id,
        ]);

        // Crear contrato que arranca en junio y cobra comisión una sola vez al inquilino
        $contract = Contract::factory()->create([
            'start_date' => '2025-06-01',
            'end_date' => '2026-05-31',
            'monthly_amount' => 100000,
            'currency' => 'ARS',
            'status' => ContractStatus::ACTIVE,
            'commission_type' => CommissionType::FIXED,
            'commission_amount' => 25000,
            'commission_payer' => CommissionPayer::TENANT,
            'is_one_time' => true,
        ]);

        ContractClient::factory()->create([
            'contract_id' => $contract->id,
            'client_id' => $client->id,
            'role' => ContractClientRole::TENANT,
        ]);

        // Ejecutar generación de cobranzas
        $service = new CollectionGenerationService();
        $service->generateForMonth(Carbon::create(2025, 6, 1));

        $collection = Collection::where('contract_id', $contract->id)->first();
        $this->assertNotNull($collection, 'No se generó la cobranza');

        $commissionItem = CollectionItem::where('collection_id', $collection->id)
            ->where('type', CollectionItemType::COMMISSION)
            ->first();

        $this->assertNotNull($commissionItem, 'No se generó ítem de comisión');
        $this->assertEquals(25000, $commissionItem->amount);
    }
}
