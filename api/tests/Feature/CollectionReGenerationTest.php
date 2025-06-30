<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\ContractExpense;
use App\Services\CollectionGenerationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Enums\CollectionItemType;

class CollectionReGenerationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_allows_regeneration_of_canceled_currency_specific_collections()
    {
        $this->prepareRequiredLookups();

        $contract = $this->createContractWithExpenses();
        $service = app(CollectionGenerationService::class);
        $period = Carbon::create(2025, 7, 1);

        // 1. Primera generación
        $collections = $service->generateForMonth($period);
        $this->assertCount(2, $collections, 'Debe generar cobranzas en 2 monedas');

        $arsCollection = $collections->firstWhere('currency', 'ARS');
        $usdCollection = $collections->firstWhere('currency', 'USD');
        $this->assertNotNull($arsCollection);
        $this->assertNotNull($usdCollection);

        // 2. Cancelar la cobranza en USD
        $usdCollection->cancel();
        $contract->refresh();
        // 3. Verificar rollback de los gastos
        $this->assertEquals(
            [false, false],
            ContractExpense::whereIn('id', [1, 2])->pluck('included_in_collection')->toArray()
        );

        // 4. Verificar en la base que la cobranza en ARS sigue activa
        $this->assertDatabaseHas('collections', [
            'contract_id' => $contract->id,
            'currency' => 'ARS',
            'period' => '2025-07',
            'status' => 'pending',
        ]);

        // 5. Ejecutar preview
        $service = app(CollectionGenerationService::class); // refrescar instancia
        $preview = $service->previewForMonth($period);

        $this->assertEquals('partial', $preview['status'], 'El estado del preview debería ser partial');
        $this->assertEquals(1, $preview['pending_generation'], 'Debe quedar 1 moneda pendiente de generar');
        $this->assertEquals(1, $preview['already_generated'], 'Debe detectar 1 ya generada');
        $this->assertEquals(1, $preview['total_contracts'], 'Debe haber 1 contrato activo');

        // 6. Generar nuevamente → debe generar solo la moneda faltante (USD)
        $newCollections = $service->generateForMonth($period);

        $this->assertCount(1, $newCollections, 'Debe generar solo una moneda faltante');
        $this->assertEquals('USD', $newCollections->first()->currency);

        // 6.1 Validar ítems en la cobranza ARS
        $arsItems = $arsCollection->items()->get();

        $this->assertCount(4, $arsItems, 'Cobranza ARS debe tener 4 ítems');
        $this->assertTrue($arsItems->contains('type', 'rent'));
        $this->assertTrue($arsItems->contains('type', 'commission'));
        $this->assertTrue($arsItems->contains('type', 'insurance'));
        $this->assertTrue($arsItems->contains('type', 'service'));

        $this->assertTrue($arsItems->contains('type', 'rent'));
        $this->assertTrue($arsItems->contains('type', 'insurance'));
        $this->assertTrue($arsItems->contains(function ($item) {
            return $item->type === CollectionItemType::Service && stripos($item->description, 'gas') !== false;
        }), 'Debe haber un ítem de servicio que incluya "gas" en la descripción');


        // 6.2 Validar ítems en la nueva cobranza USD
        $usdNewCollection = $newCollections->first();
        $usdItems = $usdNewCollection->items()->get();
        $this->assertCount(2, $usdItems, 'Cobranza USD debe tener 2 ítems');

       $this->assertTrue($usdItems->contains(function ($item) {
            return $item->type === CollectionItemType::Service && stripos($item->description, 'electricity') !== false;
        }), 'Debe haber un ítem de servicio que incluya "electricity" en la descripción');

        $this->assertTrue($usdItems->contains(function ($item) {
            return $item->type === CollectionItemType::Service && stripos($item->description, 'phone') !== false;
        }), 'Debe haber un ítem de servicio que incluya "phone" en la descripción');

        // 6.3 Validar que los service items tengan correctamente seteado expense_id, paid_by, expense_period y amount en meta y en la relación real
        $allServiceItems = $arsItems->merge($usdItems)->filter(fn($item) => $item->type === CollectionItemType::Service);

        $this->assertNotEmpty($allServiceItems, 'Debe haber al menos un ítem de tipo service');

        foreach ($allServiceItems as $item) {
            $this->assertIsArray($item->meta, "El campo meta del ítem $item->id debe ser un array");

            $this->assertArrayHasKey('expense_id', $item->meta, "El ítem $item->id debe tener 'expense_id' en meta");
            $this->assertArrayHasKey('paid_by', $item->meta, "El ítem $item->id debe tener 'paid_by' en meta");
            $this->assertArrayHasKey('expense_period', $item->meta, "El ítem $item->id debe tener 'expense_period' en meta");

            $this->assertNotNull($item->meta['expense_id'], "El expense_id en el ítem $item->id no debe ser null");

            // Validar existencia del gasto
            $expense = \App\Models\ContractExpense::find($item->meta['expense_id']);

            $this->assertNotNull($expense, "No se encontró el gasto con ID {$item->meta['expense_id']}");
            $this->assertEquals($item->collection->contract_id, $expense->contract_id, "El gasto no pertenece al mismo contrato");

            $this->assertEquals($expense->paid_by, $item->meta['paid_by'], "El paid_by del ítem $item->id no coincide");

            $this->assertEquals(
                Carbon::parse($expense->period)->startOfDay()->toDateTimeString(),
                Carbon::parse($item->meta['expense_period'])->startOfDay()->toDateTimeString(),
                "El expense_period del ítem $item->id no coincide"
            );

            $this->assertEquals(
                floatval($expense->amount),
                floatval($item->amount),
                "El monto del ítem $item->id no coincide con el del gasto"
            );
        }



        // 7. Preview final debe estar completo
        $finalPreview = $service->previewForMonth($period);
        $this->assertEquals('complete', $finalPreview['status']);
        $this->assertEquals(0, $finalPreview['pending_generation']);
    }

    protected function prepareRequiredLookups(): void
    {
        \App\Models\DocumentType::factory()->create(['id' => 1]);
        \App\Models\TaxCondition::factory()->create(['id' => 1]);
        \App\Models\CivilStatus::factory()->create(['id' => 1]);
        \App\Models\Nationality::factory()->create(['id' => 1]);

        \App\Models\PropertyType::factory()->create(['id' => 1]);
        \App\Models\Country::factory()->create(['id' => 1]);
        \App\Models\State::factory()->create(['id' => 1, 'country_id' => 1]);
        \App\Models\City::factory()->create(['id' => 1, 'state_id' => 1]);
        \App\Models\Neighborhood::factory()->create(['id' => 1, 'city_id' => 1]);
    }

    protected function createContractWithExpenses()
    {
        $contract = \App\Models\Contract::factory()->create([
            'id' => 4,
            'start_date' => '2025-07-01',
            'end_date' => '2027-06-30',
            'monthly_amount' => 1000000,
            'currency' => 'ARS',
            'insurance_required' => true,
            'insurance_amount' => 200000,
            'status' => 'active',
            'payment_day' => 10,
        ]);

        $client = \App\Models\Client::factory()->create();

        \App\Models\ContractClient::factory()->create([
            'contract_id' => $contract->id,
            'client_id' => $client->id,
            'role' => \App\Enums\ContractClientRole::TENANT,
        ]);

        // Gastos en USD
        \App\Models\ContractExpense::factory()->createMany([
            [
                'id' => 1,
                'contract_id' => $contract->id,
                'service_type' => 'electricity',
                'amount' => 110.00,
                'currency' => 'USD',
                'period' => '2025-07-01',
                'due_date' => '2025-07-01',
                'paid_by' => 'agency',
                'is_paid' => true,
                'included_in_collection' => false,
            ],
            [
                'id' => 2,
                'contract_id' => $contract->id,
                'service_type' => 'phone',
                'amount' => 120.00,
                'currency' => 'USD',
                'period' => '2025-07-01',
                'due_date' => '2025-07-01',
                'paid_by' => 'agency',
                'is_paid' => true,
                'included_in_collection' => false,
            ],
        ]);

        // Gasto en ARS
        \App\Models\ContractExpense::factory()->create([
            'id' => 3,
            'contract_id' => $contract->id,
            'service_type' => 'gas',
            'amount' => 150000.00,
            'currency' => 'ARS',
            'period' => '2025-07-01',
            'due_date' => '2025-07-01',
            'paid_by' => 'agency',
            'is_paid' => true,
            'included_in_collection' => false,
        ]);

        return $contract;
    }
}
