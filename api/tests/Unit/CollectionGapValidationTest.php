<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractClient;
use App\Models\DocumentType;
use App\Models\TaxCondition;
use App\Models\CivilStatus;
use App\Models\Nationality;
use App\Enums\ContractStatus;
use App\Enums\ContractClientRole;
use App\Services\CollectionGenerationService;
use App\Exceptions\CollectionGenerationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Carbon\Carbon;

class CollectionGapValidationTest extends TestCase
{
    use DatabaseTransactions;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_if_previous_month_is_missing()
    {
        // Crear cliente con datos mínimos válidos
        $client = Client::factory()->create([
            'document_type_id' => DocumentType::firstOrCreate(['name' => 'DNI'])->id,
            'tax_condition_id' => TaxCondition::firstOrCreate(['name' => 'Consumidor Final'])->id,
            'civil_status_id' => CivilStatus::firstOrCreate(['name' => 'Soltero'])->id,
            'nationality_id' => Nationality::firstOrCreate(['name' => 'Argentina'])->id,
        ]);

        // Crear contrato que empieza en mayo
        $contract = Contract::factory()->create([
            'start_date' => '2025-05-08',
            'end_date' => '2026-05-07',
            'monthly_amount' => 100000,
            'currency' => 'ARS',
            'status' => ContractStatus::Active,
            'prorate_first_month' => true,
        ]);

        // Asociar cliente como inquilino
        ContractClient::factory()->create([
            'contract_id' => $contract->id,
            'client_id' => $client->id,
            'role' => ContractClientRole::TENANT,
        ]);

        $service = new CollectionGenerationService();

        try {
            // Intentar generar junio sin haber generado mayo
            $service->generateForMonth(Carbon::parse('2025-06-01'));
            $this->fail('Se esperaba una excepción pero no se lanzó');
        } catch (CollectionGenerationException $e) {
            $this->assertStringContainsString('No se pudo generar cobranzas para algunos contratos.', $e->getMessage());
            $this->assertIsArray($e->errors);
            $this->assertNotEmpty($e->errors);
            $this->assertEquals($contract->id, $e->errors[0]['id']);
        }
    }
}
