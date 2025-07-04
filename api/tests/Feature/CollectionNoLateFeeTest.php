<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Collection;
use App\Models\Contract;
use App\Models\ContractClient;
use App\Models\PropertyType;
use App\Models\DocumentType;
use App\Models\TaxCondition;
use App\Models\CivilStatus;
use App\Models\Nationality;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Neighborhood;
use App\Enums\ContractStatus;
use App\Enums\ContractClientRole;
use App\Enums\CollectionItemType;
use App\Services\CollectionGenerationService;
use App\Services\CollectionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;

class CollectionNoLateFeeTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_does_not_apply_late_fee_if_paid_within_grace_period()
    {
        // Datos base requeridos
        $documentType = DocumentType::firstOrCreate(['name' => 'DNI']);
        $taxCondition = TaxCondition::firstOrCreate(['name' => 'Consumidor Final']);
        $civilStatus = CivilStatus::firstOrCreate(['name' => 'Soltero']);
        $nationality = Nationality::firstOrCreate(['name' => 'Argentina']);
        $country = Country::firstOrCreate(['name' => 'Argentina']);
        $state = State::firstOrCreate(['name' => 'Buenos Aires', 'country_id' => $country->id]);
        $city = City::firstOrCreate(['name' => 'Ciudad Autónoma de Buenos Aires', 'state_id' => $state->id]);
        $neighborhood = Neighborhood::firstOrCreate(['name' => 'Centro', 'city_id' => $city->id]);
        $propertyType = PropertyType::firstOrCreate(['name' => 'Departamento']);

        // Crear usuario y cliente
        $user = User::factory()->create();
        $this->actingAs($user);

        $client = Client::factory()->create([
            'document_type_id' => $documentType->id,
            'tax_condition_id' => $taxCondition->id,
            'civil_status_id' => $civilStatus->id,
            'nationality_id' => $nationality->id,
        ]);

        // Crear contrato con penalidad y gracia de 3 días
        $contract = Contract::factory()->create([
            'start_date' => '2025-06-01',
            'end_date' => '2026-05-31',
            'monthly_amount' => 100000,
            'currency' => 'ARS',
            'status' => ContractStatus::ACTIVE,
            'payment_day' => 5,
            'has_penalty' => true,
            'penalty_type' => 'percentage',
            'penalty_value' => 10,
            'penalty_grace_days' => 3,
        ]);

        // Asociar inquilino
        ContractClient::factory()->create([
            'contract_id' => $contract->id,
            'client_id' => $client->id,
            'role' => ContractClientRole::TENANT,
        ]);

        $period = Carbon::create(2025, 6, 1);

        // Generar la cobranza
        $generationService = new CollectionGenerationService();
        $generationService->generateForMonth($period);

        // Recuperar la cobranza generada
        $collection = Collection::where('contract_id', $contract->id)->firstOrFail();

        // Simular pago dentro del período de gracia (día 7)
        $paymentDate = $period->copy()->day(7);
        $paymentService = new CollectionService();
        $paymentService->markAsPaid($collection, $paymentDate, $user->id);

        // Verificar que NO se generó punitorio
        $collection->refresh();
        $this->assertEquals('paid', $collection->status);
        $this->assertEquals($paymentDate->toDateString(), $collection->paid_at->toDateString());

        $lateFeeItem = $collection->items()->where('type', CollectionItemType::LATE_FEE)->first();
        $this->assertNull($lateFeeItem, 'Se generó un ítem de punitorio fuera de lugar');
        $this->assertEquals(100000, $collection->total_amount);
    }
}
