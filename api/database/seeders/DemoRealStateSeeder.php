<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Carbon\Carbon;
use Faker\Factory as Faker;

// Models
use App\Models\Contract;
use App\Models\ContractAdjustment;
use App\Models\ContractCharge;
use App\Models\ContractClient;
use App\Models\ContractExpense;
use App\Models\Client;
use App\Models\Property;
use App\Models\Voucher;
use App\Models\VoucherItem;
use App\Models\ChargeType;
use App\Models\IndexType;
use App\Models\VoucherType;
use App\Models\Booklet;

// Enums
use App\Enums\ContractStatus;
use App\Enums\ContractClientRole;
use App\Enums\ContractAdjustmentType;
use App\Enums\ContractChargeStatus;
use App\Enums\PropertyStatus;
use App\Enums\ServiceType as ServiceTypeEnum;
use App\Enums\VoucherItemType;

class DemoRealStateSeeder extends Seeder
{
    private $faker;
    private $contracts = [];
    private $clients = [];
    private $properties = [];
    private $chargeTypes = [];
    private $serviceTypes = [];
    private $indexTypes = [];
    private $voucherTypes = [];
    private $booklets = [];

    public function run(): void
    {
        // Verificar que no estemos en producción
        if (App::environment('production')) {
            $this->command->error('No se puede ejecutar en producción');
            return;
        }

        // Configurar Faker con semilla determinística
        $this->faker = Faker::create('es_AR');
        $this->faker->seed(12345);
        mt_srand(12345);

        // Configurar datos específicos de Bahía Blanca
        $this->faker->addProvider(new \Faker\Provider\es_AR\Address($this->faker));
        $this->faker->addProvider(new \Faker\Provider\es_AR\PhoneNumber($this->faker));

        $this->command->info('🌱 Iniciando DemoRealStateSeeder...');

        DB::transaction(function () {
            // Desactivar eventos masivos para mejor performance
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            $this->cleanTables();
            $this->loadReferenceData();
            $this->createClients();
            $this->createProperties();
            $this->createContracts();
            $this->createContractClients();
            $this->createAdjustments();
            $this->createCharges();
            $this->createExtraCharges();
            $this->createLiquidations();
            $this->createCollections();

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        });

        $this->printSummary();
    }

    private function cleanTables(): void
    {
        $this->command->info('🧹 Limpiando tablas...');

        $tables = [
            'voucher_payments',
            'voucher_applications',
            'voucher_associations',
            'voucher_items',
            'vouchers',
            'contract_charges',
            'contract_adjustments',
            'contract_clients',
            'contract_expenses',
            'contracts',
            'clients',
            'properties'
        ];

        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }
    }

    private function loadReferenceData(): void
    {
        $this->command->info('📋 Cargando datos de referencia...');

        // Cargar tipos de cargo
        $this->chargeTypes = ChargeType::all()->keyBy('code');

        // Cargar tipos de servicio (usar enum en lugar de modelo)
        $this->serviceTypes = collect(ServiceTypeEnum::cases())->keyBy('value');

        // Cargar tipos de índice (crear si no existen)
        $this->indexTypes = $this->createIndexTypes();

        // Cargar tipos de voucher
        $this->voucherTypes = VoucherType::all()->keyBy('short_name');

        // Cargar talonarios
        $this->booklets = Booklet::all()->groupBy('currency');

        // Verificar que existen talonarios para las monedas necesarias
        if (empty($this->booklets)) {
            $this->command->warn('⚠️  No se encontraron talonarios. Creando talonarios básicos...');
            $this->createBasicBooklets();
        }
    }

    private function createIndexTypes(): array
    {
        $indexTypes = [];

        // Crear tipos de índice comunes
        $types = [
            [
                'code' => 'ICL',
                'name' => 'Índice Costo de Vida',
                'is_active' => true,
                'calculation_mode' => \App\Enums\CalculationMode::RATIO,
                'frequency' => \App\Enums\IndexFrequency::MONTHLY,
                'is_cumulative' => false
            ],
            [
                'code' => 'UVA',
                'name' => 'Unidad de Valor Adquisitivo',
                'is_active' => true,
                'calculation_mode' => \App\Enums\CalculationMode::MULTIPLICATIVE_CHAIN,
                'frequency' => \App\Enums\IndexFrequency::DAILY,
                'is_cumulative' => true
            ],
            [
                'code' => 'CER',
                'name' => 'Coeficiente de Estabilización',
                'is_active' => true,
                'calculation_mode' => \App\Enums\CalculationMode::RATIO,
                'frequency' => \App\Enums\IndexFrequency::MONTHLY,
                'is_cumulative' => false
            ],
        ];

        foreach ($types as $type) {
            $indexType = IndexType::firstOrCreate(
                ['code' => $type['code']],
                $type
            );
            $indexTypes[$type['code']] = $indexType;
        }

        return $indexTypes;
    }

    private function createBasicBooklets(): void
    {
        $liqVoucherType = $this->voucherTypes['LIQ'] ?? null;
        $cobVoucherType = $this->voucherTypes['RCB'] ?? null;

        if (!$liqVoucherType || !$cobVoucherType) {
            $this->command->error('❌ No se encontraron tipos de voucher necesarios (LIQ, RCB)');
            return;
        }

        // Crear talonarios básicos para ARS y USD
        $currencies = ['ARS', 'USD'];
        foreach ($currencies as $currency) {
            Booklet::firstOrCreate([
                'name' => "Talonario {$currency}",
                'voucher_type_id' => $liqVoucherType->id,
                'currency' => $currency,
            ], [
                'sale_point_id' => 1, // Asumir que existe
                'from_number' => 1,
                'to_number' => 999999,
                'current_number' => 0,
            ]);
        }

        // Recargar talonarios
        $this->booklets = Booklet::all()->groupBy('currency');
    }

    private function createBahiaBlancaClient(): array
    {
        // Nombres y apellidos argentinos comunes
        $firstNames = [
            'Carlos', 'María', 'Juan', 'Ana', 'Luis', 'Carmen', 'Pedro', 'Rosa',
            'José', 'Elena', 'Miguel', 'Isabel', 'Antonio', 'Pilar', 'Francisco', 'Teresa',
            'Manuel', 'Dolores', 'David', 'Concepción', 'Rafael', 'Mercedes', 'Fernando', 'Josefa',
            'Ángel', 'Francisca', 'Diego', 'Antonia', 'Sergio', 'Manuela', 'Jorge', 'Rosario'
        ];

        $lastNames = [
            'González', 'Rodríguez', 'Fernández', 'López', 'Martínez', 'Sánchez', 'Pérez', 'García',
            'Gómez', 'Martín', 'Jiménez', 'Ruiz', 'Hernández', 'Díaz', 'Moreno', 'Muñoz',
            'Álvarez', 'Romero', 'Alonso', 'Gutiérrez', 'Navarro', 'Torres', 'Domínguez', 'Vázquez',
            'Ramos', 'Gil', 'Ramírez', 'Serrano', 'Blanco', 'Suárez', 'Molina', 'Morales'
        ];

        // Calles típicas de Bahía Blanca
        $streets = [
            'Donado', 'España', 'Zeballos', 'Alem', 'Mitre', 'Rivadavia', 'San Martín',
            'Belgrano', 'Moreno', 'Sarmiento', 'Urquiza', 'Rawson', 'Brown', 'Vieytes',
            'Chiclana', 'Lavalle', 'Pueyrredón', 'Corrientes', 'Córdoba', 'Santa Fe',
            'Entre Ríos', 'Mendoza', 'Tucumán', 'Salta', 'Jujuy', 'La Rioja', 'Catamarca',
            'Santiago del Estero', 'Formosa', 'Chaco', 'Misiones', 'Neuquén', 'Río Negro',
            'Chubut', 'Santa Cruz', 'Tierra del Fuego'
        ];

        $firstName = $this->faker->randomElement($firstNames);
        $lastName = $this->faker->randomElement($lastNames);
        $street = $this->faker->randomElement($streets);
        $streetNumber = $this->faker->numberBetween(100, 9999);

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => strtolower($firstName . '.' . $lastName . '@' . $this->faker->randomElement(['gmail.com', 'hotmail.com', 'yahoo.com.ar', 'outlook.com'])),
            'phone' => '+54 291 ' . $this->faker->numberBetween(4000000, 4999999),
            'address' => $street . ' ' . $streetNumber . ', Bahía Blanca, Buenos Aires, Argentina',
            'city' => 'Bahía Blanca',
            'state' => 'Buenos Aires',
            'country' => 'Argentina',
            'postal_code' => '8000',
            'dni' => $this->faker->numberBetween(20000000, 99999999),
            'cuit' => '20-' . $this->faker->numberBetween(20000000, 99999999) . '-9',
        ];
    }

    private function createClients(): void
    {
        $this->command->info('👥 Creando clientes de Bahía Blanca...');

        // Crear 200 clientes (inquilinos y propietarios) de Bahía Blanca
        for ($i = 0; $i < 200; $i++) {
            $clientData = $this->createBahiaBlancaClient();

            $client = Client::create([
                'type' => $this->faker->randomElement(['individual', 'company']),
                'name' => $clientData['first_name'],
                'last_name' => $clientData['last_name'],
                'document_type_id' => 1, // DNI por defecto
                'document_number' => $clientData['dni'],
                'email' => $clientData['email'],
                'phone' => $clientData['phone'],
                'address' => $clientData['address'],
                'tax_condition_id' => 1, // Responsable inscripto por defecto
            ]);

            $this->clients[] = $client;
        }
    }

    private function createProperties(): void
    {
        $this->command->info('🏠 Creando propiedades en Bahía Blanca...');

        // Calles típicas de Bahía Blanca para propiedades
        $streets = [
            'Donado', 'España', 'Zeballos', 'Alem', 'Mitre', 'Rivadavia', 'San Martín',
            'Belgrano', 'Moreno', 'Sarmiento', 'Urquiza', 'Rawson', 'Brown', 'Vieytes',
            'Chiclana', 'Lavalle', 'Pueyrredón', 'Corrientes', 'Córdoba', 'Santa Fe',
            'Entre Ríos', 'Mendoza', 'Tucumán', 'Salta', 'Jujuy', 'La Rioja', 'Catamarca',
            'Santiago del Estero', 'Formosa', 'Chaco', 'Misiones', 'Neuquén', 'Río Negro',
            'Chubut', 'Santa Cruz', 'Tierra del Fuego'
        ];

        // Crear 80 propiedades en Bahía Blanca
        for ($i = 0; $i < 80; $i++) {
            $street = $this->faker->randomElement($streets);
            $streetNumber = $this->faker->numberBetween(100, 9999);

            $property = Property::create([
                'property_type_id' => 1, // Departamento por defecto
                'street' => $street,
                'number' => $streetNumber,
                'floor' => $this->faker->numberBetween(1, 20),
                'apartment' => $this->faker->bothify('##'),
                'postal_code' => '8000', // Código postal de Bahía Blanca
                'country_id' => 1, // Argentina
                'state_id' => 1, // Buenos Aires
                'city_id' => 1, // Asumir que existe Bahía Blanca en cities
                'neighborhood_id' => 1,
                'has_parking' => $this->faker->boolean(60),
                'parking_details' => $this->faker->optional(0.3)->sentence(),
                'allows_pets' => $this->faker->boolean(40),
                'status' => \App\Enums\PropertyStatus::PUBLISHED,
                'observations' => $this->faker->optional(0.3)->sentence(),
            ]);

            $this->properties[] = $property;
        }
    }

    private function createContracts(): void
    {
        $this->command->info('📄 Creando contratos...');

        $startDate = Carbon::createFromDate(2025, 1, 1);
        $endDate = Carbon::createFromDate(2025, 2, 1);

        for ($i = 0; $i < 100; $i++) {
            // Fecha de inicio aleatoria entre 2024-01-01 y 2025-02-01
            $contractStart = $this->faker->dateTimeBetween($startDate, $endDate);
            $contractStart = Carbon::parse($contractStart)->startOfMonth();

            // Duración entre 12 y 36 meses
            $durationMonths = $this->faker->numberBetween(12, 36);
            $contractEnd = $contractStart->copy()->addMonths($durationMonths);

            // Moneda: 70% ARS, 30% USD
            $currency = $this->faker->randomFloat(1, 0, 1) <= 0.7 ? 'ARS' : 'USD';

            // Monto base según moneda
            $baseAmount = $currency === 'ARS'
                ? $this->faker->numberBetween(150000, 600000)
                : $this->faker->numberBetween(200, 1200);

            $contract = Contract::create([
                'property_id' => $this->faker->randomElement($this->properties)->id,
                'start_date' => $contractStart,
                'end_date' => $contractEnd,
                'monthly_amount' => $baseAmount,
                'currency' => $currency,
                'payment_day' => $this->faker->numberBetween(1, 28),
                'prorate_first_month' => $this->faker->boolean(30),
                'prorate_last_month' => $this->faker->boolean(30),
                'commission_type' => \App\Enums\CommissionType::PERCENTAGE,
                'commission_amount' => $this->faker->numberBetween(3, 8),
                'commission_payer' => \App\Enums\CommissionPayer::TENANT,
                'is_one_time' => $this->faker->boolean(20),
                'insurance_required' => $this->faker->boolean(80),
                'insurance_amount' => $this->faker->numberBetween(5000, 25000),
                'insurance_company_name' => $this->faker->optional(0.8)->company(),
                'owner_share_percentage' => 100,
                'deposit_amount' => $baseAmount,
                'deposit_currency' => $currency,
                'deposit_type' => \App\Enums\DepositType::FIXED,
                'deposit_holder' => \App\Enums\DepositHolder::AGENCY,
                'has_penalty' => $this->faker->boolean(60),
                'penalty_type' => \App\Enums\PenaltyType::PERCENTAGE,
                'penalty_value' => $this->faker->numberBetween(1, 5),
                'penalty_grace_days' => $this->faker->numberBetween(3, 10),
                'status' => ContractStatus::ACTIVE,
                'notes' => $this->faker->optional(0.3)->sentence(),
            ]);

            $this->contracts[] = $contract;
        }
    }

    private function createContractClients(): void
    {
        $this->command->info('👥 Asignando clientes a contratos...');

        foreach ($this->contracts as $contract) {
            // Inquilinos: 60% 1, 30% 2, 10% 3-4
            $tenantCount = $this->faker->randomElement([
                1 => 0.6, 2 => 0.3, 3 => 0.07, 4 => 0.03
            ]);

            $tenantClients = $this->faker->randomElements($this->clients, $tenantCount);

            foreach ($tenantClients as $index => $client) {
                ContractClient::create([
                    'contract_id' => $contract->id,
                    'client_id' => $client->id,
                    'role' => ContractClientRole::TENANT,
                    'is_primary' => $index === 0, // El primero es principal
                    'ownership_percentage' => 0, // Se calculará después
                ]);
            }

            // Propietarios: 50% 1, 35% 2, 15% 3-4
            $ownerCount = $this->faker->randomElement([
                1 => 0.5, 2 => 0.35, 3 => 0.1, 4 => 0.05
            ]);

            $ownerClients = $this->faker->randomElements($this->clients, $ownerCount);
            $totalOwnership = 0;

            foreach ($ownerClients as $index => $client) {
                $ownership = $this->faker->numberBetween(10, 60);
                $totalOwnership += $ownership;

                ContractClient::create([
                    'contract_id' => $contract->id,
                    'client_id' => $client->id,
                    'role' => ContractClientRole::OWNER,
                    'is_primary' => $index === 0,
                    'ownership_percentage' => $ownership,
                ]);
            }

            // Normalizar ownership_percentage para que sume 100%
            $contract->clients()->where('role', ContractClientRole::OWNER)->get()->each(function ($client, $index) use ($totalOwnership) {
                if ($index === 0) {
                    // El primero recibe el resto para llegar a 100%
                    $client->update(['ownership_percentage' => 100 - ($totalOwnership - $client->ownership_percentage)]);
                } else {
                    $client->update(['ownership_percentage' => round(($client->ownership_percentage / $totalOwnership) * 100, 2)]);
                }
            });
        }
    }

    private function createAdjustments(): void
    {
        $this->command->info('📈 Creando ajustes...');

        $adjustmentCount = 0;
        foreach ($this->contracts as $contract) {
            // Política de ajuste: 45% ICL, 25% porcentaje, 10% fijo, 15% sin ajuste, 5% negociado
            $random = $this->faker->randomFloat(2, 0, 1);
            if ($random <= 0.45) {
                $adjustmentPolicy = 'index';
            } elseif ($random <= 0.70) {
                $adjustmentPolicy = 'percentage';
            } elseif ($random <= 0.80) {
                $adjustmentPolicy = 'fixed';
            } elseif ($random <= 0.95) {
                $adjustmentPolicy = 'none';
            } else {
                $adjustmentPolicy = 'negotiated';
            }

            if ($adjustmentPolicy === 'none') {
                continue;
            }

            $this->command->line("   • Contrato {$contract->id}: política {$adjustmentPolicy}");

            $contractStart = $contract->start_date;
            $contractEnd = $contract->end_date;

            // Los ajustes no se generan el mismo día del contrato
            // Comenzar entre 3-6 meses después del inicio (más realista)
            $monthsToFirstAdjustment = $this->faker->numberBetween(3, 6);
            $currentDate = $contractStart->copy()->addMonths($monthsToFirstAdjustment);

            while ($currentDate->lt($contractEnd)) {
                $adjustment = null;

                switch ($adjustmentPolicy) {
                    case 'index':
                        $adjustment = $this->createIndexAdjustment($contract, $currentDate);
                        break;
                    case 'percentage':
                        $adjustment = $this->createPercentageAdjustment($contract, $currentDate);
                        break;
                    case 'fixed':
                        $adjustment = $this->createFixedAdjustment($contract, $currentDate);
                        break;
                    case 'negotiated':
                        $adjustment = $this->createNegotiatedAdjustment($contract, $currentDate);
                        break;
                }

                if ($adjustment) {
                    $adjustmentCount++;
                    // Avanzar según frecuencia
                    $frequency = $this->faker->randomElement(['quarterly', 'semiannual', 'annual']);
                    $months = match($frequency) {
                        'quarterly' => 3,
                        'semiannual' => 6,
                        'annual' => 12,
                        default => 12
                    };
                    $currentDate->addMonths($months);
                } else {
                    $currentDate->addMonth();
                }
            }
        }

        $this->command->info("📈 Creados {$adjustmentCount} ajustes");
    }

    private function createIndexAdjustment(Contract $contract, Carbon $date): ?ContractAdjustment
    {
        try {
            $indexType = $this->faker->randomElement(array_values($this->indexTypes));
            $baseAmount = $contract->monthly_amount;

        // Los ajustes de índice NO tienen factor ni valor - para probar asignación manual
        $isApplied = false;

            return ContractAdjustment::create([
                'contract_id' => $contract->id,
                'effective_date' => $date,
                'type' => ContractAdjustmentType::INDEX,
                'index_type_id' => $indexType->id,
                'value' => null, // Sin valor - para asignación manual
                'base_amount' => $baseAmount,
                'factor' => null, // Sin factor - para asignación manual
                'index_S_date' => null, // Sin fechas - para asignación manual
                'index_F_date' => null, // Sin fechas - para asignación manual
                'index_S_value' => null, // Sin valores - para asignación manual
                'index_F_value' => null, // Sin valores - para asignación manual
                'applied_at' => null, // No aplicado
                'applied_amount' => null, // No aplicado
                'notes' => "Ajuste por {$indexType->name} - Pendiente de asignación",
            ]);
        } catch (\Exception $e) {
            $this->command->error("Error creando ajuste de índice: " . $e->getMessage());
            return null;
        }
    }

    private function createPercentageAdjustment(Contract $contract, Carbon $date): ?ContractAdjustment
    {
        $baseAmount = $contract->monthly_amount;
        $percentage = $this->faker->numberBetween(10, 35);
        $appliedAmount = round($baseAmount * (1 + $percentage / 100), 2);

        // Los ajustes NO se aplican automáticamente
        $isApplied = false;

        return ContractAdjustment::create([
            'contract_id' => $contract->id,
            'effective_date' => $date,
            'type' => ContractAdjustmentType::PERCENTAGE,
            'value' => $percentage,
            'base_amount' => $baseAmount,
            'applied_at' => null, // No aplicado
            'applied_amount' => null, // No aplicado
            'notes' => "Ajuste porcentual del {$percentage}%",
        ]);
    }

    private function createFixedAdjustment(Contract $contract, Carbon $date): ?ContractAdjustment
    {
        $baseAmount = $contract->monthly_amount;
        $fixedAmount = $this->faker->numberBetween(
            (int)($baseAmount * 0.1),
            (int)($baseAmount * 0.3)
        );

        // Los ajustes NO se aplican automáticamente
        $isApplied = false;

        return ContractAdjustment::create([
            'contract_id' => $contract->id,
            'effective_date' => $date,
            'type' => ContractAdjustmentType::FIXED,
            'value' => $fixedAmount,
            'base_amount' => $baseAmount,
            'applied_at' => null, // No aplicado
            'applied_amount' => null, // No aplicado
            'notes' => "Ajuste fijo de {$contract->currency} " . number_format($fixedAmount, 2),
        ]);
    }

    private function createNegotiatedAdjustment(Contract $contract, Carbon $date): ?ContractAdjustment
    {
        $baseAmount = $contract->monthly_amount;
        $negotiatedAmount = $this->faker->numberBetween(
            (int)($baseAmount * 0.8),
            (int)($baseAmount * 1.2)
        );

        // Los ajustes NO se aplican automáticamente
        $isApplied = false;

        return ContractAdjustment::create([
            'contract_id' => $contract->id,
            'effective_date' => $date,
            'type' => ContractAdjustmentType::NEGOTIATED,
            'value' => $negotiatedAmount,
            'base_amount' => $baseAmount,
            'applied_at' => null, // No aplicado
            'applied_amount' => null, // No aplicado
            'notes' => "Ajuste negociado por {$contract->currency} " . number_format($negotiatedAmount, 2),
        ]);
    }

    private function createCharges(): void
    {
        $this->command->info('💰 Creando cargos de renta...');

        $rentTypeId = $this->chargeTypes['RENT']->id;

        foreach ($this->contracts as $contract) {
            $currentDate = $contract->start_date->copy();
            $endDate = $contract->end_date->copy();
            $currentAmount = $contract->monthly_amount;

            while ($currentDate->lt($endDate)) {
                // Aplicar ajustes vigentes
                $adjustment = $contract->adjustments()
                    ->where('effective_date', '<=', $currentDate->endOfMonth())
                    ->whereNotNull('applied_at')
                    ->orderBy('effective_date', 'desc')
                    ->first();

                if ($adjustment) {
                    $currentAmount = $adjustment->applied_amount;
                }

                // Crear cargo de renta
                $dueDate = $currentDate->copy()->day($contract->payment_day);

                // Asegurar que due_date >= effective_date
                if ($dueDate->lt($currentDate)) {
                    $dueDate = $currentDate->copy()->addMonth()->day($contract->payment_day);
                }

                ContractCharge::create([
                    'contract_id' => $contract->id,
                    'charge_type_id' => $rentTypeId,
                    'amount' => $currentAmount,
                    'currency' => $contract->currency,
                    'effective_date' => $currentDate,
                    'due_date' => $dueDate,
                    'service_period_start' => $currentDate,
                    'service_period_end' => $currentDate->copy()->endOfMonth(),
                    'invoice_date' => $currentDate,
                    'description' => "Alquiler {$currentDate->format('Y-m')}",
                ]);

                $currentDate->addMonth();
            }
        }
    }

    private function createExtraCharges(): void
    {
        $this->command->info('🔧 Creando cargos adicionales...');

        $extraChargeTypes = [
            'RECUP_TENANT_AGENCY' => 0.15,
            'RECUP_OWNER_AGENCY' => 0.10,
            'BONIFICATION' => 0.05,
        ];

        foreach ($this->contracts as $contract) {
            // 40% de contratos tienen cargos adicionales
            if (!$this->faker->boolean(40)) {
                continue;
            }

            $currentDate = $contract->start_date->copy();
            $endDate = $contract->end_date->copy();

            while ($currentDate->lt($endDate)) {
                // Crear 1-3 cargos adicionales por mes
                $chargeCount = $this->faker->numberBetween(1, 3);

                for ($i = 0; $i < $chargeCount; $i++) {
                    $chargeTypeCode = $this->faker->randomElement(array_keys($extraChargeTypes));
                    $chargeType = $this->chargeTypes[$chargeTypeCode];

                    if (!$chargeType) continue;

                    $baseAmount = $contract->monthly_amount;
                    $amount = match($chargeTypeCode) {
                        'RECUP_TENANT_AGENCY' => $baseAmount * 0.15,
                        'RECUP_OWNER_AGENCY' => $baseAmount * 0.10,
                        'BONIFICATION' => $baseAmount * 0.05, // Positivo para bonificación (se maneja en el tipo de cargo)
                        default => $baseAmount * 0.1
                    };

                    $dueDate = $currentDate->copy()->day($contract->payment_day);

                    // Asegurar que due_date >= effective_date
                    if ($dueDate->lt($currentDate)) {
                        $dueDate = $currentDate->copy()->addMonth()->day($contract->payment_day);
                    }

                    ContractCharge::create([
                        'contract_id' => $contract->id,
                        'charge_type_id' => $chargeType->id,
                        'amount' => round($amount, 2),
                        'currency' => $contract->currency,
                        'effective_date' => $currentDate,
                        'due_date' => $dueDate,
                        'service_period_start' => $currentDate,
                        'service_period_end' => $currentDate->copy()->endOfMonth(),
                        'invoice_date' => $currentDate,
                        'description' => $chargeType->name . " {$currentDate->format('Y-m')}",
                    ]);
                }

                $currentDate->addMonth();
            }
        }
    }

    private function createLiquidations(): void
    {
        $this->command->info('📋 Creando liquidaciones...');

        $liqVoucherType = $this->voucherTypes['LIQ'];
        $rentTypeId = $this->chargeTypes['RENT']->id;

        foreach ($this->contracts as $contract) {
            $currentDate = $contract->start_date->copy();
            $endDate = $contract->end_date->copy();
            $today = now()->startOfMonth();

            // Crear liquidaciones para al menos 6 períodos alternados
            $liquidationCount = 0;
            $maxLiquidations = min(6, $currentDate->diffInMonths($endDate));

            while ($currentDate->lt($endDate) && $liquidationCount < $maxLiquidations) {
                // Saltar algunos meses para crear alternancia
                if ($this->faker->boolean(70)) {
                    $this->createLiquidationForPeriod($contract, $currentDate, $liqVoucherType, $rentTypeId);
                    $liquidationCount++;
                }

                $currentDate->addMonth();
            }
        }
    }

    private function createLiquidationForPeriod(Contract $contract, Carbon $period, VoucherType $voucherType, int $rentTypeId): void
    {
        $bookletsForCurrency = $this->booklets[$contract->currency] ?? collect();
        $booklet = $bookletsForCurrency->first();

        if (!$booklet) {
            $this->command->warn("⚠️  No se encontró talonario para moneda {$contract->currency}");
            return;
        }

        // Obtener cargos del período
        $charges = $contract->charges()
            ->where('effective_date', '>=', $period->startOfMonth())
            ->where('effective_date', '<=', $period->endOfMonth())
            ->get();

        if ($charges->isEmpty()) return;

        $totalAmount = $charges->sum('amount');

        // Crear voucher de liquidación
        $voucher = Voucher::create([
            'booklet_id' => $booklet->id,
            'voucher_type_id' => $voucherType->id,
            'currency' => $contract->currency,
            'issue_date' => $period,
            'due_date' => $period->copy()->day($contract->payment_day),
            'client_id' => $contract->clients()->where('role', ContractClientRole::TENANT)->first()->client_id,
            'contract_id' => $contract->id,
            'period' => $period->format('Y-m'),
            'total' => $totalAmount,
            'subtotal' => $totalAmount,
            'status' => 'draft',
            'number' => Voucher::where('booklet_id', $booklet->id)->max('number') + 1,
        ]);

        // Crear items del voucher
        foreach ($charges as $charge) {
            VoucherItem::create([
                'voucher_id' => $voucher->id,
                'type' => VoucherItemType::RENT,
                'description' => $charge->description,
                'quantity' => 1,
                'unit_price' => $charge->amount,
                'total' => $charge->amount,
            ]);

            // Actualizar referencia en el cargo
            $charge->update(['tenant_liquidation_voucher_id' => $voucher->id]);
        }
    }

    private function createCollections(): void
    {
        $this->command->info('💳 Creando cobranzas...');

        $cobVoucherType = $this->voucherTypes['RCB'];

        foreach ($this->contracts as $contract) {
            // Crear cobranzas para algunas liquidaciones
            $liquidations = $contract->vouchers()
                ->where('voucher_type_id', $this->voucherTypes['LIQ']->id)
                ->get();

            foreach ($liquidations as $liquidation) {
                // 60% de liquidaciones tienen cobranza
                if (!$this->faker->boolean(60)) {
                    continue;
                }

                $this->createCollectionForLiquidation($contract, $liquidation, $cobVoucherType);
            }
        }
    }

    private function createCollectionForLiquidation(Contract $contract, Voucher $liquidation, VoucherType $voucherType): void
    {
        $bookletsForCurrency = $this->booklets[$contract->currency] ?? collect();
        $booklet = $bookletsForCurrency->first();

        if (!$booklet) {
            $this->command->warn("⚠️  No se encontró talonario para moneda {$contract->currency}");
            return;
        }

        // Crear voucher de cobranza
        $collection = Voucher::create([
            'booklet_id' => $booklet->id,
            'voucher_type_id' => $voucherType->id,
            'currency' => $contract->currency,
            'issue_date' => $liquidation->issue_date->copy()->addDays($this->faker->numberBetween(1, 15)),
            'due_date' => $liquidation->due_date,
            'client_id' => $liquidation->client_id,
            'contract_id' => $contract->id,
            'period' => $liquidation->period,
            'total' => $liquidation->total,
            'subtotal' => $liquidation->subtotal,
            'status' => $this->faker->randomElement(['draft', 'issued', 'paid']),
            'number' => Voucher::where('booklet_id', $booklet->id)->max('number') + 1,
        ]);

        // Crear items de cobranza
        foreach ($liquidation->items as $item) {
            VoucherItem::create([
                'voucher_id' => $collection->id,
                'type' => VoucherItemType::RENT,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total' => $item->total,
            ]);
        }
    }

    private function printSummary(): void
    {
        $this->command->info('📊 Resumen de datos creados:');
        $this->command->info("   • Contratos: " . Contract::count());
        $this->command->info("   • Clientes: " . Client::count());
        $this->command->info("   • Propiedades: " . Property::count());
        $this->command->info("   • Ajustes: " . ContractAdjustment::count());
        $this->command->info("   • Cargos de renta: " . ContractCharge::where('charge_type_id', $this->chargeTypes['RENT']->id)->count());
        $this->command->info("   • Cargos adicionales: " . ContractCharge::where('charge_type_id', '!=', $this->chargeTypes['RENT']->id)->count());
        $this->command->info("   • Liquidaciones: " . Voucher::where('voucher_type_id', $this->voucherTypes['LIQ']->id)->count());
        $this->command->info("   • Cobranzas: " . Voucher::where('voucher_type_id', $this->voucherTypes['RCB']->id)->count());

        // Estadísticas por tipo de ajuste
        $adjustmentStats = ContractAdjustment::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get();

        $this->command->info("   • Ajustes por tipo:");
        foreach ($adjustmentStats as $stat) {
            $this->command->info("     - {$stat->type->value}: {$stat->count}");
        }

        $this->command->info('✅ DemoRealStateSeeder completado exitosamente!');
    }
}
