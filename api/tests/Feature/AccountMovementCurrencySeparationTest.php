<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Client;
use App\Models\AccountMovement;
use App\Models\Voucher;
use App\Models\Booklet;
use App\Models\VoucherType;
use App\Models\SalePoint;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccountMovementCurrencySeparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_movements_require_currency_parameter()
    {
        $client = Client::factory()->create();

        // Intentar obtener movimientos sin especificar moneda
        $response = $this->getJson("/api/clients/{$client->id}/account-movements");

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'Debe especificar una moneda para ver los movimientos de cuenta corriente'
        ]);
    }

    public function test_account_movements_are_separated_by_currency()
    {
        $client = Client::factory()->create();

        // Crear movimientos en diferentes monedas
        AccountMovement::factory()->create([
            'client_id' => $client->id,
            'date' => '2024-01-01',
            'description' => 'Movimiento ARS',
            'amount' => 1000.00,
            'currency' => 'ARS',
        ]);

        AccountMovement::factory()->create([
            'client_id' => $client->id,
            'date' => '2024-01-02',
            'description' => 'Movimiento USD',
            'amount' => 100.00,
            'currency' => 'USD',
        ]);

        // Obtener movimientos en ARS
        $responseARS = $this->getJson("/api/clients/{$client->id}/account-movements?currency=ARS");

        $responseARS->assertStatus(200);
        $dataARS = $responseARS->json('data');

        $this->assertCount(1, $dataARS);
        $this->assertEquals('ARS', $dataARS[0]['currency']);
        $this->assertEquals(1000.00, $dataARS[0]['running_balance']);

        // Obtener movimientos en USD
        $responseUSD = $this->getJson("/api/clients/{$client->id}/account-movements?currency=USD");

        $responseUSD->assertStatus(200);
        $dataUSD = $responseUSD->json('data');

        $this->assertCount(1, $dataUSD);
        $this->assertEquals('USD', $dataUSD[0]['currency']);
        $this->assertEquals(100.00, $dataUSD[0]['running_balance']);
    }

    public function test_running_balance_is_calculated_per_currency()
    {
        $client = Client::factory()->create();

        // Crear movimientos en ARS
        AccountMovement::factory()->create([
            'client_id' => $client->id,
            'date' => '2024-01-01 10:00:00',
            'description' => 'Primer movimiento ARS',
            'amount' => 1000.00,
            'currency' => 'ARS',
        ]);

        AccountMovement::factory()->create([
            'client_id' => $client->id,
            'date' => '2024-01-01 11:00:00',
            'description' => 'Segundo movimiento ARS',
            'amount' => -300.00,
            'currency' => 'ARS',
        ]);

        // Crear movimientos en USD
        AccountMovement::factory()->create([
            'client_id' => $client->id,
            'date' => '2024-01-01 10:00:00',
            'description' => 'Primer movimiento USD',
            'amount' => 100.00,
            'currency' => 'USD',
        ]);

        AccountMovement::factory()->create([
            'client_id' => $client->id,
            'date' => '2024-01-01 11:00:00',
            'description' => 'Segundo movimiento USD',
            'amount' => 50.00,
            'currency' => 'USD',
        ]);

        // Verificar saldo en ARS
        $responseARS = $this->getJson("/api/clients/{$client->id}/account-movements?currency=ARS");

        $responseARS->assertStatus(200);
        $dataARS = $responseARS->json('data');

        $this->assertCount(2, $dataARS);
        $this->assertEquals(1000.00, $dataARS[0]['running_balance']); // 1000
        $this->assertEquals(700.00, $dataARS[1]['running_balance']);  // 1000 - 300

        // Verificar saldo en USD
        $responseUSD = $this->getJson("/api/clients/{$client->id}/account-movements?currency=USD");

        $responseUSD->assertStatus(200);
        $dataUSD = $responseUSD->json('data');

        $this->assertCount(2, $dataUSD);
        $this->assertEquals(100.00, $dataUSD[0]['running_balance']); // 100
        $this->assertEquals(150.00, $dataUSD[1]['running_balance']); // 100 + 50
    }
}
