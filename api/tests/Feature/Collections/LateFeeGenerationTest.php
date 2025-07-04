<?php

namespace Tests\Feature\Collections;

use App\Enums\CollectionItemType;
use App\Models\Collection;
use App\Services\CollectionGenerationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesValidContract;

class LateFeeGenerationTest extends TestCase
{
    use RefreshDatabase;
    use CreatesValidContract;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_generates_late_fee_if_previous_collection_is_pending_and_overdue()
    {
        Carbon::setTestNow(Carbon::parse('2025-08-15'));

        $contract = $this->createValidContract([
            'monthly_amount' => 100000,
            'payment_day' => 7,
            'has_penalty' => true,
            'penalty_type' => 'percentage',
            'penalty_value' => 10,
            'penalty_grace_days' => 3,
        ]);

        // Generar cobranza del mes anterior, queda como pendiente
        Collection::create([
            'client_id' => $contract->mainTenant()->client_id,
            'contract_id' => $contract->id,
            'currency' => 'ARS',
            'issue_date' => Carbon::parse('2025-07-01'),
            'due_date' => Carbon::parse('2025-07-07'),
            'period' => '2025-07',
            'status' => 'pending',
            'total_amount' => 100000,
        ]);

        $service = new CollectionGenerationService();
        $collections = $service->generateForMonth(Carbon::parse('2025-08-01'));

        $this->assertCount(1, $collections);

        $collection = $collections->first();
        $this->assertEquals('2025-08', $collection->period);

        $penaltyItem = $collection->items()->where('type', CollectionItemType::Penalty)->first();

        $this->assertNotNull($penaltyItem);
        $this->assertEquals(10000.00, $penaltyItem->amount); // 10% de 100000
        $this->assertEquals('ARS', $penaltyItem->currency);

        $meta = $penaltyItem->meta;
        $this->assertEquals('2025-07', $meta['related_period']);
        $this->assertEquals('percentage', $meta['penalty_type']);
        $this->assertEquals(10, $meta['penalty_value']);
        $this->assertEquals(10000.00, $meta['amount']);
    }
}
