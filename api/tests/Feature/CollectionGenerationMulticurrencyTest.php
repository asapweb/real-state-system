<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\TestRequiredDataSeeder;
use Tests\Traits\CreatesValidContract;

class CollectionGenerationMulticurrencyTest extends TestCase
{
    use RefreshDatabase;
    use CreatesValidContract;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TestRequiredDataSeeder::class);
        $this->actingAs(User::factory()->create());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_generates_collections_for_multiple_currencies()
    {
        $contract = $this->createValidContract([
            'start_date' => '2025-07-01',
        ]);

        // Generamos 2 gastos en diferentes monedas
        $contract->expenses()->createMany([
            [
                'service_type' => 'electricity',
                'amount' => 100,
                'currency' => 'USD',
                'period' => '2025-07-01',
                'due_date' => '2025-07-05',
                'paid_by' => 'agency',
                'is_paid' => true,
                'included_in_collection' => false,
            ],
            [
                'service_type' => 'gas',
                'amount' => 200,
                'currency' => 'ARS',
                'period' => '2025-07-01',
                'due_date' => '2025-07-05',
                'paid_by' => 'agency',
                'is_paid' => true,
                'included_in_collection' => false,
            ]
        ]);

        $response = $this->postJson('/api/collections/generate', [
            'period' => '2025-07',
        ]);

        $response->assertStatus(200);
        $this->assertCount(2, Collection::where('contract_id', $contract->id)->where('period', '2025-07')->get());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_prevents_generation_if_any_previous_collection_is_pending()
    {
        $contract = $this->createValidContract([
            'start_date' => '2025-07-01',
        ]);

        // Generamos colección en una sola moneda (quedando otra pendiente)
        Collection::factory()->create([
            'contract_id' => $contract->id,
            'client_id' => $contract->mainTenant()->client_id,
            'period' => '2025-07',
            'currency' => 'USD',
            'status' => 'paid',
        ]);

        Collection::factory()->create([
            'contract_id' => $contract->id,
            'client_id' => $contract->mainTenant()->client_id,
            'period' => '2025-07',
            'currency' => 'ARS',
            'status' => 'pending',
        ]);

        $response = $this->postJson('/api/collections/generate', [
            'period' => '2025-08',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'No se pueden generar cobranzas porque hay períodos previos pendientes.',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_allows_generation_if_previous_collections_are_paid_or_canceled()
    {
        $contract = $this->createValidContract([
            'start_date' => '2025-07-01',
        ]);
        Collection::factory()->create([
            'contract_id' => $contract->id,
            'client_id' => $contract->mainTenant()->client_id,
            'period' => '2025-07',
            'currency' => 'USD',
            'status' => 'paid',
        ]);

        Collection::factory()->create([
            'contract_id' => $contract->id,
            'client_id' => $contract->mainTenant()->client_id,
            'period' => '2025-07',
            'currency' => 'ARS',
            'status' => 'canceled',
        ]);

        $response = $this->postJson('/api/collections/generate', [
            'period' => '2025-08',
        ]);

        $response->assertStatus(200);
        $collections = Collection::where('contract_id', $contract->id)
            ->where('period', '2025-08')
            ->get();

        $this->assertCount(1, $collections);
        $this->assertEquals('pending', $collections->first()->status); // opcional

        $this->assertGreaterThan(0, $collections->first()->items()->count());


    }
}
