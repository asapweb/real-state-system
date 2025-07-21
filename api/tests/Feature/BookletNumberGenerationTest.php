<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Booklet;
use App\Models\VoucherType;
use App\Models\SalePoint;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookletNumberGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_booklet_generates_sequential_numbers()
    {
        // Crear un tipo de voucher
        $voucherType = VoucherType::factory()->create([
            'short_name' => 'FAC',
            'letter' => 'A',
        ]);

        // Crear un punto de venta
        $salePoint = SalePoint::factory()->create([
            'number' => 1,
        ]);

        // Crear un talonario
        $booklet = Booklet::factory()->create([
            'voucher_type_id' => $voucherType->id,
            'sale_point_id' => $salePoint->id,
            'next_number' => 1,
        ]);

        // Generar números secuenciales
        $number1 = $booklet->generateNextNumber();
        $this->assertEquals('00000001', $number1);
        $this->assertEquals(2, $booklet->fresh()->next_number);

        $number2 = $booklet->generateNextNumber();
        $this->assertEquals('00000002', $number2);
        $this->assertEquals(3, $booklet->fresh()->next_number);

        $number3 = $booklet->generateNextNumber();
        $this->assertEquals('00000003', $number3);
        $this->assertEquals(4, $booklet->fresh()->next_number);
    }

    public function test_booklet_formats_voucher_numbers_correctly()
    {
        // Crear un tipo de voucher
        $voucherType = VoucherType::factory()->create([
            'short_name' => 'FAC',
            'letter' => 'A',
        ]);

        // Crear un punto de venta
        $salePoint = SalePoint::factory()->create([
            'number' => 1,
        ]);

        // Crear un talonario
        $booklet = Booklet::factory()->create([
            'voucher_type_id' => $voucherType->id,
            'sale_point_id' => $salePoint->id,
        ]);

        $number = '00000001';
        $formattedNumber = $booklet->getFormattedVoucherNumber($number);

        $this->assertEquals('FAC A 0001-00000001', $formattedNumber);
    }

    public function test_booklet_starts_from_one_if_next_number_is_null()
    {
        // Crear un talonario sin next_number
        $booklet = Booklet::factory()->create([
            'next_number' => null,
        ]);

        $number = $booklet->generateNextNumber();
        $this->assertEquals('00000001', $number);
        $this->assertEquals(2, $booklet->fresh()->next_number);
    }

    public function test_voucher_creation_uses_booklet_number_generation()
    {
        // Crear un tipo de voucher
        $voucherType = VoucherType::factory()->create();

        // Crear un talonario
        $booklet = Booklet::factory()->create([
            'voucher_type_id' => $voucherType->id,
            'next_number' => 1,
        ]);

        // Crear un cliente
        $client = \App\Models\Client::factory()->create();

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
                    'description' => 'Test service',
                    'quantity' => 1,
                    'unit_price' => 100.00,
                ],
            ],
        ];

        // Crear el voucher
        $response = $this->postJson('/api/vouchers', $voucherData);

        $response->assertStatus(201);

        // Verificar que el voucher tiene el número correcto
        $voucher = \App\Models\Voucher::find($response->json('data.id'));
        $this->assertEquals('00000001', $voucher->number);

        // Verificar que el booklet actualizó su next_number
        $booklet->refresh();
        $this->assertEquals(2, $booklet->next_number);
    }
}
