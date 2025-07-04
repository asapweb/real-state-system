<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractClient;
use App\Models\ContractExpense;
use App\Models\Collection;
use App\Models\CollectionItem;
use App\Enums\ContractStatus;
use App\Enums\ContractClientRole;
use App\Enums\CollectionItemType;
use App\Services\CollectionGenerationService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

class CollectionIncludesExpensesTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_includes_agency_paid_expenses_in_monthly_collection()
    {
        $this->actingAs(User::factory()->create());

        // 🧹 Limpiar entorno por si quedó algo en base
       CollectionItem::query()->delete();
        Collection::query()->delete();
        ContractExpense::query()->delete();
        ContractClient::query()->delete();
        Contract::query()->delete();
        Client::query()->delete();

        // Crear cliente y contrato activo
        $client = Client::factory()->create();
        $contract = Contract::factory()->create([
            'start_date' => '2025-06-01',
            'end_date' => '2026-05-31',
            'monthly_amount' => 100000,
            'currency' => 'ARS',
            'status' => ContractStatus::ACTIVE,
        ]);

        // Asociar cliente como inquilino
        ContractClient::factory()->create([
            'contract_id' => $contract->id,
            'client_id' => $client->id,
            'role' => ContractClientRole::TENANT,
        ]);

        // Recargar contrato con relación 'clients'
        $contract = $contract->fresh(['clients']);

        // Crear gasto pagado por la agencia
        ContractExpense::create([
            'contract_id' => $contract->id,
            'service_type' => 'expensas',
            'amount' => 15000,
            'currency' => 'ARS',
            'period' => '2025-06-01',
            'due_date' => '2025-06-10',
            'paid_by' => 'agency',
            'is_paid' => true,
            'included_in_collection' => false,
        ]);

        // Ejecutar generación de cobranza (puede arrojar excepción si hay contratos previos mal cargados)
        $service = new CollectionGenerationService();
        $service->generateForMonth(Carbon::create(2025, 6, 1));

        // Verificar que se haya generado la cobranza y su ítem de gasto
        $collection = Collection::where('contract_id', $contract->id)->firstOrFail();
        $serviceItem = $collection->items()->where('type', CollectionItemType::SERVICE)->first();

        $this->assertNotNull($serviceItem, 'No se generó el ítem de gasto');
        $this->assertEquals(15000, $serviceItem->amount);
        $this->assertStringContainsString('expensas', strtolower($serviceItem->description));
    }
}
