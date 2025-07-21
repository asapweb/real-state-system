<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Voucher;
use App\Models\VoucherType;
use App\Models\Booklet;
use App\Models\Client;
use App\Models\TaxRate;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VoucherTotalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_voucher_totals_are_calculated_and_saved()
    {
        // Crear un tipo de voucher
        $voucherType = VoucherType::factory()->create();

        // Crear un talonario
        $booklet = Booklet::factory()->create([
            'voucher_type_id' => $voucherType->id,
        ]);

        // Crear un cliente
        $client = Client::factory()->create();

        // Crear tax rates
        $taxRateExempt = TaxRate::factory()->create([
            'id' => 1,
            'name' => 'Exento',
            'rate' => 0,
        ]);

        $taxRateTaxed = TaxRate::factory()->create([
            'id' => 3,
            'name' => 'IVA 21%',
            'rate' => 21,
            'included_in_vat_detail' => true,
        ]);

        // Datos del voucher
        $voucherData = [
            'booklet_id' => $booklet->id,
            'issue_date' => '2024-01-15',
            'period' => '2024-01',
            'due_date' => '2024-01-20',
            'client_id' => $client->id,
            'currency' => 'ARS',
            'notes' => 'Test voucher',
            'items' => [
                [
                    'type' => 'service',
                    'description' => 'Servicio exento',
                    'quantity' => 1,
                    'unit_price' => 100.00,
                    'tax_rate_id' => 1, // Exento
                ],
                [
                    'type' => 'service',
                    'description' => 'Servicio con IVA',
                    'quantity' => 2,
                    'unit_price' => 50.00,
                    'tax_rate_id' => 3, // IVA 21%
                ],
            ],
        ];

        // Crear el voucher
        $response = $this->postJson('/api/vouchers', $voucherData);

        $response->assertStatus(201);

        $voucher = Voucher::find($response->json('data.id'));

        // Verificar que los totales se calcularon y guardaron correctamente
        $this->assertEquals(100.00, $voucher->subtotal_exempt); // Servicio exento
        $this->assertEquals(0.00, $voucher->subtotal_untaxed); // No hay items no gravados
        $this->assertEquals(100.00, $voucher->subtotal_taxed); // Servicio con IVA (2 * 50)
        $this->assertEquals(21.00, $voucher->subtotal_vat); // IVA (100 * 0.21)
        $this->assertEquals(0.00, $voucher->subtotal_other_taxes); // Sin otros tributos
        $this->assertEquals(221.00, $voucher->total); // Total: 100 + 100 + 21

        // Verificar que los items también tienen sus totales calculados
        $this->assertCount(2, $voucher->items);

        $exemptItem = $voucher->items->where('description', 'Servicio exento')->first();
        $this->assertEquals(100.00, $exemptItem->subtotal);
        $this->assertEquals(0.00, $exemptItem->vat_amount);
        $this->assertEquals(100.00, $exemptItem->subtotal_with_vat);

        $taxedItem = $voucher->items->where('description', 'Servicio con IVA')->first();
        $this->assertEquals(100.00, $taxedItem->subtotal); // 2 * 50
        $this->assertEquals(21.00, $taxedItem->vat_amount); // 100 * 0.21
        $this->assertEquals(121.00, $taxedItem->subtotal_with_vat); // 100 + 21
    }

    public function test_voucher_totals_are_recalculated_on_update()
    {
        // Crear un voucher existente
        $voucherType = VoucherType::factory()->create();
        $booklet = Booklet::factory()->create(['voucher_type_id' => $voucherType->id]);
        $client = Client::factory()->create();
        $taxRate = TaxRate::factory()->create(['rate' => 21, 'included_in_vat_detail' => true]);

        $voucher = Voucher::factory()->create([
            'booklet_id' => $booklet->id,
            'client_id' => $client->id,
            'total' => 100.00,
        ]);

        // Crear un item inicial
        $voucher->items()->create([
            'type' => 'service',
            'description' => 'Servicio inicial',
            'quantity' => 1,
            'unit_price' => 100.00,
            'tax_rate_id' => $taxRate->id,
        ]);

        // Actualizar el voucher con nuevos items
        $updateData = [
            'items' => [
                [
                    'type' => 'service',
                    'description' => 'Servicio actualizado',
                    'quantity' => 2,
                    'unit_price' => 75.00,
                    'tax_rate_id' => $taxRate->id,
                ],
            ],
        ];

        $response = $this->putJson("/api/vouchers/{$voucher->id}", $updateData);

        $response->assertStatus(200);

        $voucher->refresh();

        // Verificar que los totales se recalcularon correctamente
        $this->assertEquals(150.00, $voucher->subtotal_taxed); // 2 * 75
        $this->assertEquals(31.50, $voucher->subtotal_vat); // 150 * 0.21
        $this->assertEquals(181.50, $voucher->total); // 150 + 31.50
    }
}
