<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractClient;
use App\Models\Collection;
use App\Models\DocumentType;
use App\Models\TaxCondition;
use App\Models\CivilStatus;
use App\Models\Nationality;
use App\Enums\ContractStatus;
use App\Enums\ContractClientRole;
use App\Services\CollectionGenerationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Carbon\Carbon;

class CollectionSequentialGenerationTest extends TestCase
{
    use DatabaseTransactions;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_allows_generation_when_previous_months_exist()
    {
        // Crear cliente con datos válidos
        $client = Client::factory()->create([
            'document_type_id' => DocumentType::firstOrCreate(['name' => 'DNI'])->id,
            'tax_condition_id' => TaxCondition::firstOrCreate(['name' => 'Consumidor Final'])->id,
            'civil_status_id' => CivilStatus::firstOrCreate(['name' => 'Soltero'])->id,
            'nationality_id' => Nationality::firstOrCreate(['name' => 'Argentina'])->id,
        ]);

        // Contrato que empieza en mayo
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

        // Generar mayo
        $service->generateForMonth(Carbon::parse('2025-05-01'));
        $mayo = Collection::where('contract_id', $contract->id)->where('period', '2025-05')->first();
        $this->assertNotNull($mayo, 'No se generó la cobranza de mayo');

        // Generar junio
        $service->generateForMonth(Carbon::parse('2025-06-01'));
        $junio = Collection::where('contract_id', $contract->id)->where('period', '2025-06')->first();
        $this->assertNotNull($junio, 'No se generó la cobranza de junio');
    }
}
