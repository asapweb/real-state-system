<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Voucher;
use App\Models\VoucherType;
use App\Models\Booklet;
use App\Models\SalePoint;
use App\Models\Client;
use App\Models\VoucherPayment;
use App\Models\PaymentMethod;
use App\Models\CashMovement;
use App\Models\DocumentType;
use App\Models\TaxCondition;
use App\Models\CivilStatus;
use App\Models\Nationality;
use App\Services\VoucherService;

use PHPUnit\Framework\Attributes\Test;

class VoucherCashMovementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_cash_movements_only_for_agency_handled_payments()
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
            'name' => 'Recibo de Cobranza',
            'credit' => false,
            'affects_account' => true,
            'affects_cash' => true,
        ]);

        $salePoint = SalePoint::factory()->create();
        $booklet = Booklet::factory()->create([
            'voucher_type_id' => $voucherType->id,
            'sale_point_id' => $salePoint->id,
        ]);

        $method1 = PaymentMethod::factory()->create([
            'name' => 'Efectivo',
            'handled_by_agency' => true,
        ]);

        $method2 = PaymentMethod::factory()->create([
            'name' => 'Transferencia directa al propietario',
            'handled_by_agency' => false,
        ]);

        $voucher = Voucher::create([
            'booklet_id' => $booklet->id,
            'number' => 1,
            'issue_date' => now()->toDateString(),
            'client_id' => $client->id,
            'status' => 'draft',
            'currency' => 'ARS',
            'total' => 3000.00,
        ]);

        VoucherPayment::create([
            'voucher_id' => $voucher->id,
            'payment_method_id' => $method1->id,
            'amount' => 2000.00,
            'reference' => 'EF123',
        ]);

        VoucherPayment::create([
            'voucher_id' => $voucher->id,
            'payment_method_id' => $method2->id,
            'amount' => 1000.00,
            'reference' => 'CBU xxxxxx',
        ]);

        (new VoucherService)->issue($voucher);

        $this->assertDatabaseHas('cash_movements', [
            'voucher_id' => $voucher->id,
            'payment_method_id' => $method1->id,
            'amount' => 2000.00,
        ]);

        $this->assertDatabaseMissing('cash_movements', [
            'voucher_id' => $voucher->id,
            'payment_method_id' => $method2->id,
        ]);
    }
}
