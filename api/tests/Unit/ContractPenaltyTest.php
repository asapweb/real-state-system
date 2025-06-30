<?php

namespace Tests\Unit;

use App\Enums\PenaltyType;
use App\Models\Contract;
use App\Models\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;

class ContractPenaltyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Datos mínimos requeridos por claves foráneas de Contract -> Property -> PropertyType
    \App\Models\Country::factory()->create(['id' => 1]);
    \App\Models\State::factory()->create(['id' => 1, 'country_id' => 1]);
    \App\Models\City::factory()->create(['id' => 1, 'state_id' => 1]);
    \App\Models\Neighborhood::factory()->create(['id' => 1, 'city_id' => 1]);
    \App\Models\PropertyType::factory()->create(['id' => 1]);

    // Datos requeridos por claves foráneas de Client
    \App\Models\DocumentType::factory()->create(['id' => 1]);
    \App\Models\TaxCondition::factory()->create(['id' => 1]);
    \App\Models\CivilStatus::factory()->create(['id' => 1]);
    \App\Models\Nationality::factory()->create(['id' => 1]);
    }

    #[Test]
    public function it_returns_null_if_contract_has_no_penalty_enabled()
    {
        $contract = Contract::factory()->create([
            'has_penalty' => false,
        ]);

        $result = $contract->calculatePenaltyForPeriod(Carbon::now());

        $this->assertNull($result);
    }

    #[Test]
    public function it_returns_null_if_there_is_no_previous_pending_collection()
    {
        $contract = Contract::factory()->create([
            'has_penalty' => true,
        ]);

        $result = $contract->calculatePenaltyForPeriod(Carbon::now());

        $this->assertNull($result);
    }

    #[Test]
    public function it_returns_null_if_grace_period_is_not_expired()
    {
        $contract = Contract::factory()->create([
            'has_penalty' => true,
            'penalty_type' => PenaltyType::FIXED,
            'penalty_value' => 500,
            'penalty_grace_days' => 5,
        ]);

        Collection::factory()->create([
            'contract_id' => $contract->id,
            'period' => Carbon::now()->subMonth()->format('Y-m'),
            'status' => 'pending',
            'due_date' => now()->subDays(3),
            'total_amount' => 10000,
        ]);

        $this->travelTo(now());

        $this->assertNull($contract->calculatePenaltyForPeriod(Carbon::now()));
    }

    #[Test]
    public function it_returns_fixed_penalty_if_grace_expired()
    {
        $contract = Contract::factory()->create([
            'has_penalty' => true,
            'penalty_type' => PenaltyType::FIXED,
            'penalty_value' => 800,
            'penalty_grace_days' => 0,
        ]);

        Collection::factory()->create([
            'contract_id' => $contract->id,
            'period' => Carbon::now()->subMonth()->format('Y-m'),
            'status' => 'pending',
            'due_date' => now()->subDays(5),
            'total_amount' => 10000,
        ]);

        $this->travelTo(now());

        $penalty = $contract->calculatePenaltyForPeriod(Carbon::now());

        $this->assertEquals(800, $penalty['amount']);
        $this->assertEquals(PenaltyType::FIXED, $penalty['penalty_type']);
    }

    #[Test]
    public function it_returns_percentage_penalty_if_grace_expired()
    {
        $contract = Contract::factory()->create([
            'has_penalty' => true,
            'penalty_type' => PenaltyType::PERCENTAGE,
            'penalty_value' => 10,
            'penalty_grace_days' => 2,
        ]);

        Collection::factory()->create([
            'contract_id' => $contract->id,
            'period' => Carbon::now()->subMonth()->format('Y-m'),
            'status' => 'pending',
            'due_date' => now()->subDays(5),
            'total_amount' => 12000,
        ]);

        $this->travelTo(now());

        $penalty = $contract->calculatePenaltyForPeriod(Carbon::now());

        $this->assertEquals(1200, $penalty['amount']);
        $this->assertEquals(PenaltyType::PERCENTAGE, $penalty['penalty_type']);
    }
}
