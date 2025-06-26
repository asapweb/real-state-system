<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractClient;
use App\Models\Collection;
use App\Models\CollectionItem;
use App\Models\DocumentType;
use App\Models\TaxCondition;
use App\Models\CivilStatus;
use App\Models\Nationality;
use App\Enums\ContractStatus;
use App\Enums\ContractClientRole;
use App\Enums\CollectionItemType;
use App\Services\CollectionGenerationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Carbon\Carbon;

class ProratedCollectionTest extends TestCase
{
    use DatabaseTransactions;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_generates_prorated_collection_for_first_month()
    {
        // Datos base mínimos para el cliente
        $client = Client::factory()->create([
            'document_type_id' => DocumentType::firstOrCreate(['name' => 'DNI'])->id,
            'tax_condition_id' => TaxCondition::firstOrCreate(['name' => 'Consumidor Final'])->id,
            'civil_status_id' => CivilStatus::firstOrCreate(['name' => 'Soltero'])->id,
            'nationality_id' => Nationality::firstOrCreate(['name' => 'Argentina'])->id,
        ]);

        // Contrato que empieza el 8 de mayo
        $contract = Contract::factory()->create([
            'start_date' => '2025-05-08',
            'end_date' => '2026-05-07',
            'monthly_amount' => 100000,
            'currency' => 'ARS',
            'status' => ContractStatus::Active,
            'prorate_first_month' => true,
        ]);

        ContractClient::factory()->create([
            'contract_id' => $contract->id,
            'client_id' => $client->id,
            'role' => ContractClientRole::TENANT,
        ]);
$this->assertTrue(
    Contract::activeDuring(Carbon::parse('2025-06-01'))->where('id', $contract->id)->exists(),
    'El contrato no entra en activeDuring()'
);

        $service = new CollectionGenerationService();
        $service->generateForMonth(Carbon::create(2025, 5, 1));

        $collection = Collection::where('contract_id', $contract->id)->first();
        $this->assertNotNull($collection, 'La cobranza no fue generada');

        $item = CollectionItem::where('collection_id', $collection->id)->first();
        $this->assertNotNull($item, 'No se creó item de alquiler');
        $this->assertEquals(CollectionItemType::Rent, $item->type);
        $this->assertLessThan(100000, $item->amount, 'El importe debería estar prorrateado');

        $meta = $item->meta;
        $this->assertEquals('2025-05-08', $meta['from']);
        $this->assertEquals('2025-05-31', $meta['to']);
        $this->assertEquals(24, $meta['prorated_days']);
        $this->assertEquals(31, $meta['month_days']);
    }
}
