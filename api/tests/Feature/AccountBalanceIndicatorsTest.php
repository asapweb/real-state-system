<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Client;
use App\Models\AccountMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccountBalanceIndicatorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_balances_endpoint_returns_correct_data()
    {
        $client = Client::factory()->create();

        // Crear movimientos en diferentes monedas
        AccountMovement::factory()->create([
            'client_id' => $client->id,
            'date' => '2024-01-01',
            'description' => 'Movimiento ARS 1',
            'amount' => 1000.00,
            'currency' => 'ARS',
        ]);

        AccountMovement::factory()->create([
            'client_id' => $client->id,
            'date' => '2024-01-02',
            'description' => 'Movimiento ARS 2',
            'amount' => -300.00,
            'currency' => 'ARS',
        ]);

        AccountMovement::factory()->create([
            'client_id' => $client->id,
            'date' => '2024-01-03',
            'description' => 'Movimiento USD',
            'amount' => 100.00,
            'currency' => 'USD',
        ]);

        $response = $this->getJson("/api/clients/{$client->id}/account-balances");

        $response->assertStatus(200);

        $data = $response->json();

        $this->assertArrayHasKey('balances', $data);
        $this->assertArrayHasKey('default_currency', $data);
        $this->assertEquals('ARS', $data['default_currency']);

        $balances = $data['balances'];

        // Verificar que hay 2 monedas
        $this->assertCount(2, $balances);

        // Encontrar ARS
        $arsBalance = collect($balances)->firstWhere('currency', 'ARS');
        $this->assertNotNull($arsBalance);
        $this->assertEquals(700.00, $arsBalance['balance']); // 1000 - 300
        $this->assertEquals(2, $arsBalance['movement_count']);

        // Encontrar USD
        $usdBalance = collect($balances)->firstWhere('currency', 'USD');
        $this->assertNotNull($usdBalance);
        $this->assertEquals(100.00, $usdBalance['balance']);
        $this->assertEquals(1, $usdBalance['movement_count']);
    }

    public function test_account_balances_returns_empty_when_no_movements()
    {
        $client = Client::factory()->create();

        $response = $this->getJson("/api/clients/{$client->id}/account-balances");

        $response->assertStatus(200);

        $data = $response->json();

        $this->assertArrayHasKey('balances', $data);
        $this->assertArrayHasKey('default_currency', $data);
        $this->assertEquals('ARS', $data['default_currency']);
        $this->assertCount(0, $data['balances']);
    }

    public function test_account_balances_handles_negative_balances()
    {
        $client = Client::factory()->create();

        // Crear movimientos que resulten en saldo negativo
        AccountMovement::factory()->create([
            'client_id' => $client->id,
            'date' => '2024-01-01',
            'description' => 'Pago inicial',
            'amount' => -500.00,
            'currency' => 'ARS',
        ]);

        AccountMovement::factory()->create([
            'client_id' => $client->id,
            'date' => '2024-01-02',
            'description' => 'Otro pago',
            'amount' => -300.00,
            'currency' => 'ARS',
        ]);

        $response = $this->getJson("/api/clients/{$client->id}/account-balances");

        $response->assertStatus(200);

        $data = $response->json();
        $balances = $data['balances'];

        $this->assertCount(1, $balances);

        $arsBalance = $balances[0];
        $this->assertEquals('ARS', $arsBalance['currency']);
        $this->assertEquals(-800.00, $arsBalance['balance']); // -500 - 300
        $this->assertEquals(2, $arsBalance['movement_count']);
    }
}
