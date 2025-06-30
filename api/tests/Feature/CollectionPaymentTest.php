<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Collection;
use App\Models\ContractClient;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use App\Enums\CollectionItemType;
use App\Enums\ContractClientRole;
use PHPUnit\Framework\Attributes\Test;

class CollectionPaymentTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_marks_a_collection_as_paid_and_applies_late_fee()
    {
        // Arrange: usuario autenticado
        $user = User::factory()->create();
        $this->actingAs($user);

        // Crear cliente e inquilino
        $client = Client::factory()->create();

        // Crear contrato con penalidad por mora
        $contract = Contract::factory()->create([
            'monthly_amount' => 100000,
            'payment_day' => 5,
            'has_penalty' => true,
            'penalty_type' => 'percentage',
            'penalty_value' => 10,
            'penalty_grace_days' => 0,
        ]);

        // Asociar cliente al contrato como inquilino
        ContractClient::factory()->create([
            'contract_id' => $contract->id,
            'client_id' => $client->id,
            'role' => ContractClientRole::TENANT,
        ]);

        // Crear colección simulando que vence el 5 y se paga el 10
        $period = Carbon::createFromDate(2025, 6, 1);
        $collection = Collection::factory()->create([
            'contract_id' => $contract->id,
            'client_id' => $client->id,
            'currency' => 'ARS',
            'period' => $period->format('Y-m'),
            'status' => 'pending',
            'total_amount' => 0,
            'due_date' => $period->copy()->day(5),
        ]);

        // 👉 Agregar ítem de alquiler manualmente
        $collection->items()->create([
            'type' => CollectionItemType::Rent,
            'description' => 'Alquiler mes Junio',
            'quantity' => 1,
            'unit_price' => 100000,
            'amount' => 100000,
            'currency' => 'ARS',
        ]);

        // Act: marcar como pagada el día 10 (fuera de término)
        $response = $this->postJson("/api/collections/{$collection->id}/mark-as-paid", [
            'paid_at' => $period->copy()->day(10)->toDateString(),
        ]);

        // Assert
        $response->assertOk();
        $collection->refresh();

        $this->assertEquals('paid', $collection->status);
        $this->assertEquals($user->id, $collection->paid_by_user_id);
        $this->assertEquals($period->copy()->day(10)->toDateString(), $collection->paid_at->toDateString());

        $lateFeeItem = $collection->items()->where('type', CollectionItemType::LateFee)->first();
        $this->assertNotNull($lateFeeItem);
        $this->assertEquals(10000, $lateFeeItem->amount);

        $this->assertEquals(110000, $collection->total_amount);
    }
}
