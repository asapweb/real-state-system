<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Voucher;
use App\Models\VoucherType;
use App\Models\Booklet;
use App\Models\Client;
use App\Models\AccountMovement;
use App\Services\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VoucherAccountMovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_movement_created_when_voucher_issued_with_affects_account()
    {
        // Crear un tipo de voucher que afecta la cuenta
        $voucherType = VoucherType::factory()->create([
            'affects_account' => true,
            'credit' => false, // Débito (aumenta la deuda)
        ]);

        // Crear un talonario
        $booklet = Booklet::factory()->create([
            'voucher_type_id' => $voucherType->id,
        ]);

        // Crear un cliente
        $client = Client::factory()->create();

        // Crear un voucher en estado draft
        $voucher = Voucher::factory()->create([
            'booklet_id' => $booklet->id,
            'voucher_type_id' => $voucherType->id,
            'client_id' => $client->id,
            'status' => 'draft',
            'total' => 1000.00,
            'currency' => 'ARS',
        ]);

        // Verificar que no hay movimientos de cuenta antes de emitir
        $this->assertDatabaseMissing('account_movements', [
            'voucher_id' => $voucher->id,
        ]);

        // Emitir el voucher
        $voucherService = new VoucherService();
        $voucherService->issue($voucher);

        // Verificar que se creó el movimiento de cuenta
        $this->assertDatabaseHas('account_movements', [
            'voucher_id' => $voucher->id,
            'client_id' => $client->id,
            'amount' => 1000.00, // Positivo porque credit = false
            'currency' => 'ARS',
            'is_initial' => false,
        ]);

        // Verificar que el voucher cambió a estado issued
        $this->assertEquals('issued', $voucher->fresh()->status);
    }

    public function test_account_movement_not_created_when_voucher_type_does_not_affect_account()
    {
        // Crear un tipo de voucher que NO afecta la cuenta
        $voucherType = VoucherType::factory()->create([
            'affects_account' => false,
        ]);

        // Crear un talonario
        $booklet = Booklet::factory()->create([
            'voucher_type_id' => $voucherType->id,
        ]);

        // Crear un cliente
        $client = Client::factory()->create();

        // Crear un voucher en estado draft
        $voucher = Voucher::factory()->create([
            'booklet_id' => $booklet->id,
            'voucher_type_id' => $voucherType->id,
            'client_id' => $client->id,
            'status' => 'draft',
            'total' => 1000.00,
        ]);

        // Emitir el voucher
        $voucherService = new VoucherService();
        $voucherService->issue($voucher);

        // Verificar que NO se creó movimiento de cuenta
        $this->assertDatabaseMissing('account_movements', [
            'voucher_id' => $voucher->id,
        ]);

        // Verificar que el voucher cambió a estado issued
        $this->assertEquals('issued', $voucher->fresh()->status);
    }

    public function test_credit_voucher_creates_negative_account_movement()
    {
        // Crear un tipo de voucher de crédito que afecta la cuenta
        $voucherType = VoucherType::factory()->create([
            'affects_account' => true,
            'credit' => true, // Crédito (reduce la deuda)
        ]);

        // Crear un talonario
        $booklet = Booklet::factory()->create([
            'voucher_type_id' => $voucherType->id,
        ]);

        // Crear un cliente
        $client = Client::factory()->create();

        // Crear un voucher en estado draft
        $voucher = Voucher::factory()->create([
            'booklet_id' => $booklet->id,
            'voucher_type_id' => $voucherType->id,
            'client_id' => $client->id,
            'status' => 'draft',
            'total' => 500.00,
            'currency' => 'ARS',
        ]);

        // Emitir el voucher
        $voucherService = new VoucherService();
        $voucherService->issue($voucher);

        // Verificar que se creó el movimiento de cuenta con monto negativo
        $this->assertDatabaseHas('account_movements', [
            'voucher_id' => $voucher->id,
            'client_id' => $client->id,
            'amount' => -500.00, // Negativo porque credit = true
            'currency' => 'ARS',
        ]);
    }

    public function test_duplicate_account_movement_not_created()
    {
        // Crear un tipo de voucher que afecta la cuenta
        $voucherType = VoucherType::factory()->create([
            'affects_account' => true,
        ]);

        // Crear un talonario
        $booklet = Booklet::factory()->create([
            'voucher_type_id' => $voucherType->id,
        ]);

        // Crear un cliente
        $client = Client::factory()->create();

        // Crear un voucher en estado draft
        $voucher = Voucher::factory()->create([
            'booklet_id' => $booklet->id,
            'voucher_type_id' => $voucherType->id,
            'client_id' => $client->id,
            'status' => 'draft',
            'total' => 1000.00,
        ]);

        // Emitir el voucher dos veces
        $voucherService = new VoucherService();
        $voucherService->issue($voucher);
        $voucherService->issue($voucher);

        // Verificar que solo se creó un movimiento de cuenta
        $this->assertEquals(1, AccountMovement::where('voucher_id', $voucher->id)->count());
    }
}
