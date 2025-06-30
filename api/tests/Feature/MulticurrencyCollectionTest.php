<?php

namespace Tests\Feature;

use App\Enums\CollectionItemType;
use App\Models\Collection;
use App\Models\ContractExpense;
use App\Services\CollectionGenerationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesValidContract;

class MulticurrencyCollectionTest extends TestCase
{
    use RefreshDatabase;
    use CreatesValidContract;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_cobranzas_in_multiple_currencies_and_cancellation_flow()
    {
        $this->withoutExceptionHandling();

        // Crear contrato válido desde el trait
        $contract = $this->createValidContract([
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
        ]);

        $client = $contract->mainTenant();

        // Agregar gastos en dos monedas para el mes 2025-07
        ContractExpense::factory()->create([
            'contract_id' => $contract->id,
            'service_type' => 'electricity',
            'amount' => 100.00,
            'currency' => 'ARS',
            'period' => '2025-07',
            'due_date' => '2025-07-10',
            'paid_by' => 'agency',
            'is_paid' => true,
            'included_in_collection' => false,
        ]);

        ContractExpense::factory()->create([
            'contract_id' => $contract->id,
            'service_type' => 'internet',
            'amount' => 200.00,
            'currency' => 'USD',
            'period' => '2025-07',
            'due_date' => '2025-07-10',
            'paid_by' => 'agency',
            'is_paid' => true,
            'included_in_collection' => false,
        ]);

        // 🔁 Refrescar la relación expenses antes de llamar al servicio
        $contract->unsetRelation('expenses');
        $contract->load('expenses');

        $period = Carbon::create(2025, 7, 1);

        // Generar cobranzas para 2025-07 (debería generar una por moneda)
        $generated = (new CollectionGenerationService())->generateForMonth($period);
        $this->assertCount(2, $generated);

        $collectionArs = $generated->firstWhere('currency', 'ARS');
        $collectionUsd = $generated->firstWhere('currency', 'USD');

        $this->assertNotNull($collectionArs);
        $this->assertNotNull($collectionUsd);

        // Cancelar la de ARS
        $collectionArs->update(['status' => 'canceled']);

        // Volver a generar para 2025-07 (debería regenerar solo la de ARS)
        $regenerated = (new CollectionGenerationService())->generateForMonth($period);
        $this->assertCount(1, $regenerated);
        $this->assertEquals('ARS', $regenerated->first()->currency);

        // Crear una cobranza manual para el mismo contrato y cliente (sin período)
        Collection::create([
            'client_id' => $client->id,
            'contract_id' => $contract->id,
            'currency' => 'ARS',
            'issue_date' => '2025-07-15',
            'due_date' => '2025-07-20',
            'period' => null,
            'status' => 'pending',
            'total_amount' => 123.45,
        ]);

        // Generar para 2025-08 (debería generar ambas monedas nuevamente)
        $nextPeriod = Carbon::create(2025, 8, 1);
        $nextGenerated = (new CollectionGenerationService())->generateForMonth($nextPeriod);
        $this->assertCount(2, $nextGenerated);
        $this->assertTrue($nextGenerated->pluck('currency')->contains('ARS'));
        $this->assertTrue($nextGenerated->pluck('currency')->contains('USD'));
    }
}
