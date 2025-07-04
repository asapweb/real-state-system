<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Voucher;
use App\Models\VoucherItem;
use App\Models\TaxRate;
use App\Models\PaymentMethod;
use App\Models\VoucherType;
use App\Models\Booklet;
use App\Models\SalePoint;

class VoucherTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_a_voucher_with_items_and_payments()
    {
        $taxRate = TaxRate::factory()->create(['rate' => 21.00, 'included_in_vat_detail' => true]);
        $paymentMethod = PaymentMethod::factory()->create(['name' => 'Efectivo']);
        $voucherType = VoucherType::factory()->create(['name' => 'Cobranza X', 'credit' => false]);
        $salePoint = SalePoint::factory()->create(['number' => 1]);
        $booklet = Booklet::factory()->create([
            'voucher_type_id' => $voucherType->id,
            'sale_point_id' => $salePoint->id,
        ]);

        $voucher = Voucher::create([
            'booklet_id' => $booklet->id,
            'number' => 1,
            'issue_date' => now()->toDateString(),
            'status' => 'issued',
            'currency' => 'ARS',
            'total' => 1210.00,
        ]);

        $voucher->items()->create([
            'type' => 'rent',
            'description' => 'Alquiler julio',
            'quantity' => 1,
            'unit_price' => 1000.00,
            'subtotal' => 1000.00,
            'vat_amount' => 210.00,
            'subtotal_with_vat' => 1210.00,
            'tax_rate_id' => $taxRate->id,
        ]);

        $voucher->payments()->create([
            'payment_method_id' => $paymentMethod->id,
            'amount' => 1210.00,
            'reference' => 'EF-001',
        ]);

        $this->assertDatabaseHas('vouchers', ['id' => $voucher->id, 'total' => 1210.00]);
        $this->assertEquals(1, $voucher->items()->count());
        $this->assertEquals(1, $voucher->payments()->count());
    }
}
