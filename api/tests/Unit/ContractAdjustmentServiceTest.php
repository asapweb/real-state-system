<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ContractAdjustmentService;
use App\Models\ContractAdjustment;
use App\Models\Contract;
use App\Models\IndexType;
use App\Models\IndexValue;
use App\Enums\ContractAdjustmentType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ContractAdjustmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ContractAdjustmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ContractAdjustmentService();
    }

    /** @test */
    public function it_assigns_percentage_value_for_percentage_mode()
    {
        // Crear tipo de índice con modo percentage
        $indexType = IndexType::factory()->create([
            'calculation_mode' => 'percentage',
            'code' => 'TEST'
        ]);

        // Crear contrato
        $contract = Contract::factory()->create([
            'start_date' => '2025-01-01',
            'monthly_amount' => 1000.00
        ]);

        // Crear valor de índice para modo percentage
        $indexValue = IndexValue::factory()->create([
            'index_type_id' => $indexType->id,
            'period' => '2025-02',
            'percentage' => 5.50,
            'date' => null,
            'value' => null
        ]);

        // Crear ajuste
        $adjustment = ContractAdjustment::factory()->create([
            'contract_id' => $contract->id,
            'index_type_id' => $indexType->id,
            'type' => ContractAdjustmentType::INDEX,
            'effective_date' => '2025-02-01',
            'value' => null
        ]);

        // Ejecutar el servicio
        $result = $this->service->assignIndexValue($adjustment);

        // Verificar que se asignó el valor correcto
        $this->assertTrue($result);
        $this->assertEquals(5.50, $adjustment->fresh()->value);
    }

    /** @test */
    public function it_assigns_ratio_value_for_ratio_mode()
    {
        // Crear tipo de índice con modo ratio
        $indexType = IndexType::factory()->create([
            'calculation_mode' => 'ratio',
            'code' => 'TEST'
        ]);

        // Crear contrato
        $contract = Contract::factory()->create([
            'start_date' => '2025-01-01',
            'monthly_amount' => 1000.00
        ]);

        // Crear valores de índice para modo ratio
        IndexValue::factory()->create([
            'index_type_id' => $indexType->id,
            'period' => null,
            'percentage' => null,
            'date' => '2025-01-15',
            'value' => 100.0000 // valor base
        ]);

        IndexValue::factory()->create([
            'index_type_id' => $indexType->id,
            'period' => null,
            'percentage' => null,
            'date' => '2025-02-15',
            'value' => 105.0000 // valor actual
        ]);

        // Crear ajuste
        $adjustment = ContractAdjustment::factory()->create([
            'contract_id' => $contract->id,
            'index_type_id' => $indexType->id,
            'type' => ContractAdjustmentType::INDEX,
            'effective_date' => '2025-02-01',
            'value' => null
        ]);

        // Ejecutar el servicio
        $result = $this->service->assignIndexValue($adjustment);

        // Verificar que se asignó el valor correcto
        $this->assertTrue($result);
        $this->assertEquals(5.00, $adjustment->fresh()->value); // (105-100)/100 * 100 = 5%
    }

    /** @test */
    public function it_applies_percentage_adjustment_correctly()
    {
        // Crear tipo de índice con modo percentage
        $indexType = IndexType::factory()->create([
            'calculation_mode' => 'percentage',
            'code' => 'TEST'
        ]);

        // Crear contrato
        $contract = Contract::factory()->create([
            'monthly_amount' => 1000.00
        ]);

        // Crear ajuste con valor percentage
        $adjustment = ContractAdjustment::factory()->create([
            'contract_id' => $contract->id,
            'index_type_id' => $indexType->id,
            'type' => ContractAdjustmentType::INDEX,
            'value' => 5.50, // 5.5%
            'applied_at' => null
        ]);

        // Ejecutar el servicio
        $this->service->apply($adjustment);

        // Verificar que se aplicó correctamente: 1000 * (1 + 5.5/100) = 1055
        $this->assertEquals(1055.00, $adjustment->fresh()->applied_amount);
    }

    /** @test */
    public function it_applies_ratio_adjustment_correctly()
    {
        // Crear tipo de índice con modo ratio
        $indexType = IndexType::factory()->create([
            'calculation_mode' => 'ratio',
            'code' => 'TEST'
        ]);

        // Crear contrato
        $contract = Contract::factory()->create([
            'monthly_amount' => 1000.00
        ]);

        // Crear ajuste con valor ratio
        $adjustment = ContractAdjustment::factory()->create([
            'contract_id' => $contract->id,
            'index_type_id' => $indexType->id,
            'type' => ContractAdjustmentType::INDEX,
            'value' => 1.05, // ratio de 1.05
            'applied_at' => null
        ]);

        // Ejecutar el servicio
        $this->service->apply($adjustment);

        // Verificar que se aplicó correctamente: 1000 * 1.05 = 1050
        $this->assertEquals(1050.00, $adjustment->fresh()->applied_amount);
    }
}
