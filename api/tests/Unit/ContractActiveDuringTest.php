<?php

namespace Tests\Unit;

use App\Models\Contract;
use App\Enums\ContractStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Carbon\Carbon;

class ContractActiveDuringTest extends TestCase
{
    use DatabaseTransactions;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_includes_contracts_that_start_mid_month()
    {
        $contract = Contract::factory()->create([
            'start_date' => '2025-05-08',
            'end_date' => '2026-05-07',
            'status' => ContractStatus::Active,
        ]);

        $period = Carbon::parse('2025-05-01');


        $found = Contract::activeDuring($period)->where('id', $contract->id)->exists();

        $this->assertTrue($found, 'El contrato no fue incluido en el scope activeDuring()');
    }
}
