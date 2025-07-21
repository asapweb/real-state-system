<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Voucher;
use App\Models\VoucherItem;
use App\Models\TaxRate;
use App\Services\VoucherCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VoucherCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed necessary tax rates
        $this->artisan('db:seed', ['--class' => 'TaxRateSeeder']);
    }

    public function test_calculate_item()
    {
        $service = new VoucherCalculationService();
        $taxRate = TaxRate::where('rate', '21.00')->first();

        $item = new VoucherItem([
            'quantity' => 2,
            'unit_price' => 100,
            'tax_rate_id' => $taxRate->id,
        ]);
        $item->setRelation('taxRate', $taxRate);

        $service->calculateItem($item);

        $this->assertEquals(200, $item->subtotal);
        $this->assertEquals(42, $item->vat_amount);
        $this->assertEquals(242, $item->subtotal_with_vat);
    }

    public function test_calculate_voucher_totals()
    {
        $service = new VoucherCalculationService();
        $voucher = new Voucher();

        // Tax rates
        $taxRateExempt = TaxRate::find(1); // Exento
        $taxRateUntaxed = TaxRate::find(2); // No Gravado
        $taxRate21 = TaxRate::where('rate', '21.00')->first();
        $taxRate10_5 = TaxRate::where('rate', '10.50')->first();

        $items = collect([
            // Item Gravado 21%
            new VoucherItem(['quantity' => 1, 'unit_price' => 1000, 'tax_rate_id' => $taxRate21->id]),
            // Item Gravado 10.5%
            new VoucherItem(['quantity' => 1, 'unit_price' => 500, 'tax_rate_id' => $taxRate10_5->id]),
            // Item Exento
            new VoucherItem(['quantity' => 1, 'unit_price' => 200, 'tax_rate_id' => $taxRateExempt->id]),
            // Item No Gravado
            new VoucherItem(['quantity' => 1, 'unit_price' => 100, 'tax_rate_id' => $taxRateUntaxed->id]),
        ]);

        // Manually set relations for calculation
        $items[0]->setRelation('taxRate', $taxRate21);
        $items[1]->setRelation('taxRate', $taxRate10_5);
        $items[2]->setRelation('taxRate', $taxRateExempt);
        $items[3]->setRelation('taxRate', $taxRateUntaxed);

        $voucher->setRelation('items', $items);

        $service->calculateVoucher($voucher);

        $this->assertEquals(200, $voucher->subtotal_exempt);
        $this->assertEquals(100, $voucher->subtotal_untaxed);
        $this->assertEquals(1500, $voucher->subtotal_taxed); // 1000 + 500
        $this->assertEquals(262.5, $voucher->subtotal_vat); // (1000 * 0.21) + (500 * 0.105) = 210 + 52.5
        $this->assertEquals(0, $voucher->subtotal_other_taxes);

        // Total = (1000 + 210) + (500 + 52.5) + 200 + 100 = 1210 + 552.5 + 200 + 100 = 2062.5
        $this->assertEquals(2062.5, $voucher->total);
    }
}
