<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractClient;
use App\Models\Collection;
use App\Models\CollectionItem;
use App\Models\DocumentType;
use App\Models\TaxCondition;
use App\Models\CivilStatus;
use App\Models\Nationality;
use App\Enums\ContractStatus;
use App\Enums\ContractClientRole;
use App\Enums\PenaltyType;
use App\Enums\CollectionItemType;
use App\Services\CollectionGenerationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

class PenaltyGenerationTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_generates_penalty_item_when_previous_collection_is_pending_and_due_date_has_passed()
    {
        $client = Client::factory()->create([
            'document_type_id' => DocumentType::firstOrCreate(['name' => 'DNI'])->id,
            'tax_condition_id' => TaxCondition::firstOrCreate(['name' => 'Consumidor Final'])->id,
            'civil_status_id' => CivilStatus::firstOrCreate(['name' => 'Soltero'])->id,
            'nationality_id' => Nationality::firstOrCreate(['name' => 'Argentina'])->id,
        ]);

        $contract = Contract::factory()->create([
            'start_date' => '2025-05-01',
            'end_date' => '2026-05-31',
            'monthly_amount' => 100000,
            'currency' => 'ARS',
            'status' => ContractStatus::Active,
            'payment_day' => 10,
            'has_penalty' => true,
            'penalty_type' => PenaltyType::PERCENTAGE,
            'penalty_value' => 5,
            'penalty_grace_days' => 0,
        ]);

        ContractClient::factory()->create([
            'contract_id' => $contract->id,
            'client_id' => $client->id,
            'role' => ContractClientRole::TENANT,
        ]);

        Collection::factory()->create([
            'contract_id' => $contract->id,
            'client_id' => $client->id,
            'period' => '2025-05',
            'due_date' => Carbon::parse('2025-05-10'),
            'issue_date' => Carbon::parse('2025-05-01'),
            'currency' => 'ARS',
            'total_amount' => 100000,
            'status' => 'pending',
        ]);

        Carbon::setTestNow(Carbon::parse('2025-06-21'));

        $service = new CollectionGenerationService();
        $service->generateForMonth(Carbon::parse('2025-06-01'));

        $collection = Collection::where('contract_id', $contract->id)
            ->where('period', '2025-06')
            ->first();

        $this->assertNotNull($collection, 'La cobranza de junio no fue generada');

        $penaltyItem = CollectionItem::where('collection_id', $collection->id)
            ->where('type', CollectionItemType::Penalty)
            ->first();

        $this->assertNotNull($penaltyItem, 'No se generó ítem de punitorio');
        $this->assertEquals(5000, $penaltyItem->amount, 'El importe del punitorio no es el esperado (5% de 100000)');
        $this->assertEquals('2025-05', $penaltyItem->meta['related_period']);
    }
}
