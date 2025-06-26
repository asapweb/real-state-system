<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Contract;
use App\Models\Collection;
use App\Models\ContractAdjustment;
use App\Enums\ContractAdjustmentType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

class ContractTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_calculates_prorated_rent_for_first_month()
    {
        $contract = Contract::factory()->create([
            'start_date' => '2025-06-15',
            'end_date' => '2026-06-15',
            'monthly_amount' => 100000,
            'prorate_first_month' => true,
        ]);

        $period = Carbon::create(2025, 6, 1);

        $rent = $contract->calculateRentForPeriod($period);

        $expectedAmount = round(100000 * (16 / 30), 2); // 53333.33

        $this->assertIsArray($rent);
        $this->assertArrayHasKey('amount', $rent);
        $this->assertEquals($expectedAmount, $rent['amount']);
        $this->assertArrayHasKey('meta', $rent);
        $this->assertArrayHasKey('prorated_days', $rent['meta']);
    }

    #[Test]
    public function it_applies_percentage_adjustment_to_rent()
    {
        $period = Carbon::create(2025, 7, 1);

        $contract = Contract::factory()->create([
            'monthly_amount' => 100000,
        ]);

        $adjustment = ContractAdjustment::factory()->create([
            'contract_id' => $contract->id,
            'effective_date' => '2025-07-01',
            'type' => ContractAdjustmentType::Percentage,
            'value' => 10,
        ]);

        $this->assertEquals(ContractAdjustmentType::Percentage, $adjustment->type);
        $this->assertTrue(Carbon::parse($adjustment->effective_date)->lessThanOrEqualTo($period->endOfMonth()));

        $adjusted = $contract->applyAdjustments(100000, $period);

        $this->assertEquals(110000, $adjusted);
    }

    #[Test]
    public function it_calculates_penalty_for_overdue_collection()
    {
        $period = Carbon::create(2025, 6, 1);
        $previousPeriod = $period->copy()->subMonth();

        $contract = Contract::factory()->create([
            'has_penalty' => true,
            'penalty_type' => 'fixed',
            'penalty_value' => 2500,
            'penalty_grace_days' => 0,
        ]);

        $collection = Collection::factory()->create([
            'contract_id' => $contract->id,
            'period' => $previousPeriod->format('Y-m'),
            'due_date' => now()->subDays(10),
            'status' => 'pending',
            'total_amount' => 100000,
        ]);

        $this->assertTrue(now()->gt(Carbon::parse($collection->due_date)));

        $penalty = $contract->calculatePenaltyForPeriod($period);

        $this->assertIsArray($penalty);
        $this->assertEquals(2500, $penalty['amount']);
        $this->assertEquals($previousPeriod->format('Y-m'), $penalty['related_period']);
    }
}
