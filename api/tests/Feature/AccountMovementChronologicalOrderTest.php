<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Client;
use App\Models\AccountMovement;
use App\Models\Voucher;
use App\Models\Booklet;
use App\Models\VoucherType;
use App\Models\SalePoint;
use App\Services\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AccountMovementChronologicalOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_movements_are_ordered_chronologically()
    {
        // Crear datos necesarios
        $voucherType = VoucherType::factory()->create([
            'affects_account' => true,
        ]);

        $salePoint = SalePoint::factory()->create();
        $booklet = Booklet::factory()->create([
            'voucher_type_id' => $voucherType->id,
            'sale_point_id' => $salePoint->id,
        ]);

        $client = Client::factory()->create();

        // Crear vouchers en orden inverso (más reciente primero)
        $voucher2 = Voucher::factory()->create([
            'client_id' => $client->id,
            'booklet_id' => $booklet->id,
            'number' => '00000002',
            'status' => 'draft',
            'total' => 2000.00,
            'issue_date' => '2024-01-15',
        ]);

        $voucher1 = Voucher::factory()->create([
            'client_id' => $client->id,
            'booklet_id' => $booklet->id,
            'number' => '00000001',
            'status' => 'draft',
            'total' => 1000.00,
            'issue_date' => '2024-01-10',
        ]);

        // Emitir vouchers en orden inverso (voucher2 primero, voucher1 después)
        $voucherService = new VoucherService();

        // Emitir voucher2 primero
        $voucherService->issue($voucher2);

        // Esperar un momento para que las fechas sean diferentes
        sleep(1);

        // Emitir voucher1 después
        $voucherService->issue($voucher1);

        // Obtener los movimientos de cuenta
        $response = $this->getJson("/api/clients/{$client->id}/account-movements");

        $response->assertStatus(200);

        $data = $response->json('data');

        // Verificar que hay 2 movimientos
        $this->assertCount(2, $data);

        // Verificar que están ordenados cronológicamente (más antiguo primero)
        $firstMovement = $data[0];
        $secondMovement = $data[1];

        // El primer movimiento debería ser el voucher2 (emitido primero)
        $this->assertEquals('00000002', $firstMovement['voucher_number']);
        $this->assertEquals(2000.00, $firstMovement['amount']);
        $this->assertEquals(2000.00, $firstMovement['running_balance']);

        // El segundo movimiento debería ser el voucher1 (emitido después)
        $this->assertEquals('00000001', $secondMovement['voucher_number']);
        $this->assertEquals(1000.00, $secondMovement['amount']);
        $this->assertEquals(3000.00, $secondMovement['running_balance']); // 2000 + 1000
    }

    public function test_running_balance_calculation_is_correct()
    {
        $client = Client::factory()->create();

        // Crear movimientos manualmente con fechas específicas
        AccountMovement::factory()->create([
            'client_id' => $client->id,
            'date' => '2024-01-01 10:00:00',
            'description' => 'Primer movimiento',
            'amount' => 1000.00,
            'currency' => 'ARS',
        ]);

        AccountMovement::factory()->create([
            'client_id' => $client->id,
            'date' => '2024-01-01 11:00:00',
            'description' => 'Segundo movimiento',
            'amount' => -500.00,
            'currency' => 'ARS',
        ]);

        AccountMovement::factory()->create([
            'client_id' => $client->id,
            'date' => '2024-01-01 12:00:00',
            'description' => 'Tercer movimiento',
            'amount' => 300.00,
            'currency' => 'ARS',
        ]);

        $response = $this->getJson("/api/clients/{$client->id}/account-movements");

        $response->assertStatus(200);

        $data = $response->json('data');

        // Verificar que hay 3 movimientos
        $this->assertCount(3, $data);

        // Verificar el cálculo del saldo acumulado
        $this->assertEquals(1000.00, $data[0]['running_balance']); // 1000
        $this->assertEquals(500.00, $data[1]['running_balance']);   // 1000 - 500
        $this->assertEquals(800.00, $data[2]['running_balance']);   // 500 + 300
    }
}
