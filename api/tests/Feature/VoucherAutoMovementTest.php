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
use App\Models\DocumentType;
use App\Models\TaxCondition;
use App\Models\CivilStatus;
use App\Models\Nationality;

use PHPUnit\Framework\Attributes\Test;

class VoucherAutoMovementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_account_movement_automatically_when_voucher_is_issued()
    {
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
            'voucher_id' => $voucher->id,
            'client_id' => $client->id,
            'amount' => 1500.00,
        ]);
    }
}
