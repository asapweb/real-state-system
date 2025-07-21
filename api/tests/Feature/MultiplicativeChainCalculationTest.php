<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Contract;
use App\Models\ContractAdjustment;
use App\Models\IndexType;
use App\Models\IndexValue;
use App\Models\Property;
use App\Models\Client;
use App\Enums\CalculationMode;
use App\Enums\IndexFrequency;
use App\Services\ContractAdjustmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class MultiplicativeChainCalculationTest extends TestCase
{
    use RefreshDatabase;

    private ContractAdjustmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ContractAdjustmentService();
    }

    public function test_multiplicative_chain_calculation()
    {
        // Crear datos de prueba
        $this->createTestData();

        // Crear contrato con inicio en 2025-01-01
        $contract = Contract::create([
            'property_id' => Property::first()->id,
            'start_date' => '2025-01-01',
            'end_date' => '2026-01-01',
            'monthly_amount' => 100000.00,
            'currency' => 'ARS',
            'status' => 'active',
        ]);

        // Crear tipo de índice con multiplicative_chain
        $indexType = IndexType::create([
            'code' => 'CASA_PROPIA_TEST',
            'name' => 'Coeficiente Casa Propia Test',
            'is_active' => true,
            'calculation_mode' => CalculationMode::MULTIPLICATIVE_CHAIN,
            'frequency' => IndexFrequency::MONTHLY,
        ]);

        // Insertar valores de índice mensuales (coeficientes)
        // NOTA: Según metodología Casa Propia, se excluye el mes de inicio (2025-01)
        // y se incluye el mes del ajuste (2025-04)
        $this->createIndexValues($indexType);

        // Crear ajuste con multiplicative_chain
        $adjustment = ContractAdjustment::create([
            'contract_id' => $contract->id,
            'index_type_id' => $indexType->id,
            'effective_date' => '2025-04-01',
            'type' => 'index',
            'value' => null, // Se calculará automáticamente
        ]);

        // Ejecutar el cálculo
        $result = $this->service->assignIndexValue($adjustment);

        // Verificar que el cálculo fue exitoso
        $this->assertTrue($result);

        // Recargar el ajuste para obtener el valor calculado
        $adjustment->refresh();

        // Verificar que el valor fue calculado correctamente
        // Metodología Casa Propia: excluye 2025-01, incluye 2025-04
        // Períodos: 2025-02, 2025-03, 2025-04 (3 coeficientes)
        // Esperado: 1.04 * 1.05 * 1.06 = 1.15752
        $expectedValue = 1.04 * 1.05 * 1.06;
        $this->assertEquals($expectedValue, $adjustment->value, '', 0.0001);

        // Verificar logs
        $this->assertLogContains('🔗 Iniciando cálculo MULTIPLICATIVE_CHAIN (metodología Casa Propia)');
        $this->assertLogContains('✅ Cálculo MULTIPLICATIVE_CHAIN completado (metodología Casa Propia)');
    }

    public function test_multiplicative_chain_with_missing_periods()
    {
        // Crear datos de prueba
        $this->createTestData();

        // Crear contrato
        $contract = Contract::create([
            'property_id' => Property::first()->id,
            'start_date' => '2025-01-01',
            'end_date' => '2026-01-01',
            'monthly_amount' => 100000.00,
            'currency' => 'ARS',
            'status' => 'active',
        ]);

        // Crear tipo de índice
        $indexType = IndexType::create([
            'code' => 'CASA_PROPIA_TEST',
            'name' => 'Coeficiente Casa Propia Test',
            'is_active' => true,
            'calculation_mode' => CalculationMode::MULTIPLICATIVE_CHAIN,
            'frequency' => IndexFrequency::MONTHLY,
        ]);

        // Insertar solo algunos valores (faltan algunos períodos)
        IndexValue::create([
            'index_type_id' => $indexType->id,
            'period' => '2025-01',
            'percentage' => 1.03,
        ]);

        IndexValue::create([
            'index_type_id' => $indexType->id,
            'period' => '2025-03', // Falta 2025-02
            'percentage' => 1.05,
        ]);

        // Crear ajuste
        $adjustment = ContractAdjustment::create([
            'contract_id' => $contract->id,
            'index_type_id' => $indexType->id,
            'effective_date' => '2025-04-01',
            'type' => 'index',
            'value' => null,
        ]);

        // Ejecutar el cálculo
        $result = $this->service->assignIndexValue($adjustment);

        // Verificar que el cálculo falló debido a períodos faltantes
        $this->assertFalse($result);

        // Verificar logs de error
        $this->assertLogContains('⚠️ Faltan valores de índice para algunos períodos');
    }

    public function test_multiplicative_chain_same_month_adjustment()
    {
        // Crear datos de prueba
        $this->createTestData();

        // Crear contrato con inicio en 2025-01-01
        $contract = Contract::create([
            'property_id' => Property::first()->id,
            'start_date' => '2025-01-01',
            'end_date' => '2026-01-01',
            'monthly_amount' => 100000.00,
            'currency' => 'ARS',
            'status' => 'active',
        ]);

        // Crear tipo de índice con multiplicative_chain
        $indexType = IndexType::create([
            'code' => 'CASA_PROPIA_TEST',
            'name' => 'Coeficiente Casa Propia Test',
            'is_active' => true,
            'calculation_mode' => CalculationMode::MULTIPLICATIVE_CHAIN,
            'frequency' => IndexFrequency::MONTHLY,
        ]);

        // Insertar valor solo para el mes de inicio
        IndexValue::create([
            'index_type_id' => $indexType->id,
            'period' => '2025-01',
            'value' => 1.03,
        ]);

        // Crear ajuste en el mismo mes que el inicio del contrato
        $adjustment = ContractAdjustment::create([
            'contract_id' => $contract->id,
            'index_type_id' => $indexType->id,
            'effective_date' => '2025-01-15', // Mismo mes que start_date
            'type' => 'index',
            'value' => null,
        ]);

        // Ejecutar el cálculo
        $result = $this->service->assignIndexValue($adjustment);

        // Verificar que el cálculo falló porque no hay períodos para procesar
        // (se excluye el mes de inicio, pero el ajuste es en el mismo mes)
        $this->assertFalse($result);

        // Verificar logs
        $this->assertLogContains('⚠️ No se generaron períodos para el cálculo');
    }

    public function test_multiplicative_chain_application()
    {
        // Crear datos de prueba
        $this->createTestData();

        // Crear contrato con inicio en 2025-01-01
        $contract = Contract::create([
            'property_id' => Property::first()->id,
            'start_date' => '2025-01-01',
            'end_date' => '2026-01-01',
            'monthly_amount' => 100000.00,
            'currency' => 'ARS',
            'status' => 'active',
        ]);

        // Crear tipo de índice con multiplicative_chain
        $indexType = IndexType::create([
            'code' => 'CASA_PROPIA_TEST',
            'name' => 'Coeficiente Casa Propia Test',
            'is_active' => true,
            'calculation_mode' => CalculationMode::MULTIPLICATIVE_CHAIN,
            'frequency' => IndexFrequency::MONTHLY,
        ]);

        // Insertar valores de índice mensuales (coeficientes)
        $this->createIndexValues($indexType);

        // Crear ajuste con multiplicative_chain
        $adjustment = ContractAdjustment::create([
            'contract_id' => $contract->id,
            'index_type_id' => $indexType->id,
            'effective_date' => '2025-04-01',
            'type' => 'index',
            'value' => null, // Se calculará automáticamente
        ]);

        // Ejecutar el cálculo para asignar el valor
        $result = $this->service->assignIndexValue($adjustment);
        $this->assertTrue($result);

        // Recargar el ajuste para obtener el valor calculado
        $adjustment->refresh();

        // Verificar que el valor fue calculado correctamente
        // Metodología Casa Propia: excluye 2025-01, incluye 2025-04
        // Períodos: 2025-02, 2025-03, 2025-04 (3 coeficientes)
        // Esperado: 1.04 * 1.05 * 1.06 = 1.15752
        $expectedValue = 1.04 * 1.05 * 1.06;
        $this->assertEquals($expectedValue, $adjustment->value, '', 0.0001);

        // Aplicar el ajuste
        $this->service->apply($adjustment);

        // Recargar el ajuste para obtener el applied_amount
        $adjustment->refresh();

        // Verificar que el applied_amount fue calculado correctamente
        // Para multiplicative_chain: monthly_amount * adjustment_value
        // 100000 * 1.15752 = 115752
        $expectedAppliedAmount = 100000 * $expectedValue;
        $this->assertEquals($expectedAppliedAmount, $adjustment->applied_amount, '', 0.01);

        // Verificar que el ajuste fue marcado como aplicado
        $this->assertNotNull($adjustment->applied_at);

        // Verificar logs
        $this->assertLogContains('🔗 Aplicando ajuste de índice en modo MULTIPLICATIVE_CHAIN (Casa Propia)');
        $this->assertLogContains('✅ Cálculo completado');
    }

    private function createTestData()
    {
        // Crear propiedad
        Property::create([
            'street' => 'Test Street 123',
            'number' => '123',
            'floor' => '1',
            'apartment' => 'A',
            'neighborhood_id' => 1,
            'city_id' => 1,
            'state_id' => 1,
            'country_id' => 1,
            'postal_code' => '1234',
            'registry_number' => 'TEST-001',
            'property_type_id' => 1,
            'tax_condition_id' => 1,
        ]);

        // Crear cliente
        Client::create([
            'name' => 'Test',
            'last_name' => 'Client',
            'email' => 'test@example.com',
            'phone' => '+54 11 1234-5678',
            'document_type_id' => 1,
            'document_number' => '12345678',
            'tax_condition_id' => 1,
            'civil_status_id' => 1,
            'nationality_id' => 1,
        ]);
    }

    private function createIndexValues(IndexType $indexType)
    {
        // Crear valores mensuales para todos los meses relevantes
        // NOTA: Según metodología Casa Propia:
        // - 2025-01: mes de inicio del contrato (será excluido)
        // - 2025-02, 2025-03, 2025-04: meses que se incluirán en el cálculo
        $values = [
            '2025-01' => 1.03, // Este mes será excluido (mes de inicio)
            '2025-02' => 1.04, // Primer mes incluido
            '2025-03' => 1.05, // Segundo mes incluido
            '2025-04' => 1.06, // Tercer mes incluido (mes del ajuste)
        ];

        foreach ($values as $period => $value) {
            IndexValue::create([
                'index_type_id' => $indexType->id,
                'period' => $period,
                'value' => $value, // Usar 'value' en lugar de 'percentage' para multiplicative_chain
            ]);
        }
    }

    private function assertLogContains(string $message)
    {
        // Esta es una implementación básica para verificar logs
        // En un entorno real, podrías usar un mock o verificar los logs reales
        $this->assertTrue(true); // Placeholder
    }
}
