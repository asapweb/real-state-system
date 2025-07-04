<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\AccountMovement;
use App\Models\Voucher;
use App\Models\VoucherType;
use App\Models\Booklet;
use App\Models\SalePoint;
use App\Models\Client;

use function PHPUnit\Framework\assertEquals;

class VoucherAccountMovementTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_account_movement_when_voucher_is_issued()
    {
        $documentType = \App\Models\DocumentType::factory()->create();
        $taxCondition = \App\Models\TaxCondition::factory()->create();
        $civilStatus = \App\Models\CivilStatus::factory()->create();
        $nationality = \App\Models\Nationality::factory()->create();
        $client = Client::factory()->create([
            'document_type_id' => $documentType->id,
            'tax_condition_id' => $taxCondition->id,
            'civil_status_id' => $civilStatus->id,
            'nationality_id' => $nationality->id,
        ]);
        $voucherType = VoucherType::factory()->create([
            'name' => 'Cobranza X',
            'credit' => false,
            'affects_account' => true,
            'affects_cash' => false,
        ]);
        $salePoint = SalePoint::factory()->create();
        $booklet = Booklet::factory()->create([
            'voucher_type_id' => $voucherType->id,
            'sale_point_id' => $salePoint->id,
        ]);

        $voucher = Voucher::create([
            'booklet_id' => $booklet->id,
            'number' => 1,
            'issue_date' => now()->toDateString(),
            'client_id' => $client->id,
            'status' => 'issued',
            'currency' => 'ARS',
            'total' => 1500.00,
        ]);

        $this->assertDatabaseHas('account_movements', [
            'client_id' => $client->id,
            'voucher_id' => $voucher->id,
            'amount' => 1500.00,
        ]);

        assertEquals(1500.00, AccountMovement::where('client_id', $client->id)->sum('amount'));
    }
}
