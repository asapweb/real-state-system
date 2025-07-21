<?php

namespace Tests\Feature\Voucher;

use App\Enums\ContractClientRole;
use App\Models\Booklet;
use App\Models\Client;
use App\Models\Contract;
use App\Models\DocumentType;
use App\Models\SalePoint;
use App\Models\TaxCondition;
use App\Models\Voucher;
use App\Models\VoucherType;
use App\Models\PropertyType;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Neighborhood;
use App\Models\Property;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class VoucherUpdateTest extends TestCase
{
    use DatabaseTransactions;

    public static function voucherTypes(): array
    {
        return [
            ['FAC'],
            ['COB'],
            ['N/D'],
            ['N/C'],
            ['LIQ'],
            ['RCB'],
            ['RPG'],
        ];
    }

    #[Test]
    #[DataProvider('voucherTypes')]
    public function test_update_voucher_across_all_types(string $type): void
    {
        $voucher = $this->createBasicVoucher();
        $voucher->update(['voucher_type_short_name' => $type]);

        $user = $this->createAndAuthenticateUser();

        // Crear TaxRate para los items
        $taxRate = $this->createTaxRate();

        // Preparar items según el tipo de voucher
        $items = [];
        if (in_array($type, ['FAC', 'N/C', 'N/D', 'COB'])) {
            $items = [
                [
                    'description' => 'Servicio de alquiler',
                    'quantity' => 1,
                    'unit_price' => 100000,
                    'tax_rate_id' => $taxRate->id,
                ],
            ];
        } elseif ($type === 'LIQ') {
            $items = [
                [
                    'description' => 'Liquidación de gastos',
                    'amount' => 50000,
                ],
            ];
        }

        $payload = [
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'notes' => 'Actualizado desde test',
            'client_id' => $voucher->client_id,
            'currency' => 'ARS',
            'client_name' => 'Test Client',
            'items' => $items,
        ];

        // Agregar campos específicos según el tipo
        if ($type === 'COB') {
            $payload['contract_id'] = $voucher->contract_id;
        }

        $response = $this->putJson("/api/vouchers/{$voucher->id}", $payload);

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $voucher->id,
            'notes' => 'Actualizado desde test',
        ], 'data');
    }



    #[Test]
    public function test_update_voucher_validation_errors(): void
    {
        // Crear datos básicos
        $voucher = $this->createBasicVoucher();
        $user = \App\Models\User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($user);

        // Test 1: Campos obligatorios faltantes
        $response = $this->putJson("/api/vouchers/{$voucher->id}", []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['client_id', 'currency', 'client_name']);

        // Test 2: client_id inválido
        $response = $this->putJson("/api/vouchers/{$voucher->id}", [
            'client_id' => 99999, // ID inexistente
            'currency' => 'ARS',
            'client_name' => 'Test',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['client_id']);

        // Test 3: currency inválido
        $response = $this->putJson("/api/vouchers/{$voucher->id}", [
            'client_id' => $voucher->client_id,
            'currency' => 'INVALID',
            'client_name' => 'Test',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['currency']);
    }

    #[Test]
    public function test_cannot_update_issued_voucher(): void
    {
        $voucher = $this->createBasicVoucher();

        // Cambiar estado a issued
        $voucher->update(['status' => 'issued']);

        $user = \App\Models\User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($user);

        $payload = [
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'notes' => 'Intento de actualización',
            'client_id' => $voucher->client_id,
            'currency' => 'ARS',
            'client_name' => 'Test',
        ];

        $response = $this->putJson("/api/vouchers/{$voucher->id}", $payload);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Solo se pueden editar comprobantes en estado borrador.'
        ]);

        // Verificar que no se actualizó en la base de datos
        $this->assertDatabaseMissing('vouchers', [
            'id' => $voucher->id,
            'notes' => 'Intento de actualización',
        ]);
    }

    #[Test]
    public function test_cannot_update_canceled_voucher(): void
    {
        $voucher = $this->createBasicVoucher();

        // Cambiar estado a cancelled
        $voucher->update(['status' => 'cancelled']);

        $user = \App\Models\User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($user);

        $payload = [
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'notes' => 'Intento de actualización',
            'client_id' => $voucher->client_id,
            'currency' => 'ARS',
            'client_name' => 'Test',
        ];

        $response = $this->putJson("/api/vouchers/{$voucher->id}", $payload);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Solo se pueden editar comprobantes en estado borrador.'
        ]);
    }

    #[Test]
    public function test_cannot_change_client_id_on_issued_voucher(): void
    {
        $voucher = $this->createBasicVoucher();
        $originalClientId = $voucher->client_id;

        // Crear un segundo cliente usando DocumentType existente o crear uno nuevo
        $existingDocumentType = DocumentType::where('name', 'DNI')->first();
        $documentType = $existingDocumentType ?: DocumentType::create(['name' => 'DNI2', 'is_default' => false]);

        $existingTaxCondition = TaxCondition::where('name', 'Consumidor Final')->first();
        $taxCondition = $existingTaxCondition ?: TaxCondition::create(['name' => 'Consumidor Final2', 'is_default' => false]);

        $newClient = Client::create([
            'type' => 'individual',
            'name' => 'María',
            'last_name' => 'García',
            'email' => 'maria@test.com',
            'document_type_id' => $documentType->id,
            'document_number' => '87654321',
            'tax_condition_id' => $taxCondition->id,
        ]);

        // Cambiar estado a issued
        $voucher->update(['status' => 'issued']);

        $user = \App\Models\User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($user);

        $payload = [
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'notes' => 'Intento de cambio de cliente',
            'client_id' => $newClient->id, // Intentar cambiar el cliente
            'currency' => 'ARS',
            'client_name' => $newClient->name,
        ];

        $response = $this->putJson("/api/vouchers/{$voucher->id}", $payload);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Solo se pueden editar comprobantes en estado borrador.'
        ]);

        // Verificar que el client_id no cambió
        $this->assertDatabaseHas('vouchers', [
            'id' => $voucher->id,
            'client_id' => $originalClientId,
        ]);
    }

    #[Test]
    public function test_can_update_draft_voucher_successfully(): void
    {
        $voucher = $this->createBasicVoucher();
        $user = $this->createAndAuthenticateUser();

        // Crear TaxRate para los items
        $taxRate = $this->createTaxRate();

        $payload = [
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'notes' => 'Actualización exitosa',
            'client_id' => $voucher->client_id,
            'currency' => 'ARS',
            'client_name' => 'Test Client',
            'items' => [
                [
                    'description' => 'Servicio de alquiler',
                    'quantity' => 1,
                    'unit_price' => 100000,
                    'tax_rate_id' => $taxRate->id,
                ],
            ],
        ];

        $response = $this->putJson("/api/vouchers/{$voucher->id}", $payload);

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $voucher->id,
            'notes' => 'Actualización exitosa',
        ], 'data');
    }

    /**
     * Helper method to create a basic voucher for testing
     */
    private function createBasicVoucher(): Voucher
    {
        // Crear PropertyType usando el helper
        $propertyType = $this->createPropertyType();

        // Crear modelos geográficos requeridos usando helpers
        $country = $this->createCountry();
        $state = $this->createState($country);
        $city = $this->createCity($state);
        $neighborhood = $this->createNeighborhood($city);

        // Crear Property con todos los campos requeridos
        $property = \App\Models\Property::create([
            'property_type_id' => $propertyType->id,
            'street' => 'Test Street',
            'number' => '123',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'neighborhood_id' => $neighborhood->id,
            'status' => 'draft',
        ]);

        // Crear Client
        $client = \App\Models\Client::create([
            'name' => 'Test Client',
            'email' => 'test@example.com',
            'phone' => '123456789',
            'type' => 'tenant',
        ]);

        // Crear Contract
        $contract = \App\Models\Contract::create([
            'property_id' => $property->id,
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'monthly_amount' => 100000,
            'currency' => 'ARS',
        ]);

        // Crear Booklet
        $voucherType = \App\Models\VoucherType::create([
            'name' => 'Factura',
            'short_name' => 'FAC',
            'letter' => 'A',
        ]);

        $booklet = \App\Models\Booklet::create([
            'name' => 'Talonario Test',
            'voucher_type_id' => $voucherType->id,
            'next_number' => 1,
        ]);

        // Crear Voucher
        return \App\Models\Voucher::create([
            'booklet_id' => $booklet->id,
            'voucher_type_id' => $voucherType->id,
            'voucher_type_short_name' => 'FAC',
            'number' => 1,
            'issue_date' => now(),
            'due_date' => now()->addDays(5),
            'client_id' => $client->id,
            'contract_id' => $contract->id,
            'currency' => 'ARS',
            'status' => 'draft',
            'client_name' => 'Test Client',
        ]);
    }

    // ============================================================================
    // TESTS PARA COMPROBANTES FISCALES (FAC, N/D, N/C)
    // ============================================================================

    #[Test]
    public function test_fiscal_voucher_update_with_complete_items(): void
    {
        $voucher = $this->createFiscalVoucher('FAC');
        $user = $this->createAndAuthenticateUser();

        // Crear TaxRate para los items
        $taxRate = $this->createTaxRate();

        $payload = [
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'notes' => 'Actualización con items completos',
            'client_id' => $voucher->client_id,
            'currency' => 'ARS',
            'client_name' => 'Test',
            'items' => [
                [
                    'description' => 'Servicio de alquiler',
                    'quantity' => 1,
                    'unit_price' => 100000,
                    'tax_rate_id' => $taxRate->id,
                ],
                [
                    'description' => 'Gastos administrativos',
                    'quantity' => 1,
                    'unit_price' => 5000,
                    'tax_rate_id' => null,
                ],
            ],
        ];

        $response = $this->putJson("/api/vouchers/{$voucher->id}", $payload);

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $voucher->id,
            'notes' => 'Actualización con items completos',
        ], 'data');

        // Verificar que los items se guardaron
        $this->assertDatabaseHas('voucher_items', [
            'voucher_id' => $voucher->id,
            'description' => 'Servicio de alquiler',
            'quantity' => 1,
            'unit_price' => 100000,
        ]);

        $this->assertDatabaseHas('voucher_items', [
            'voucher_id' => $voucher->id,
            'description' => 'Gastos administrativos',
            'quantity' => 1,
            'unit_price' => 5000,
        ]);
    }

    #[Test]
    public function test_fiscal_voucher_validation_empty_items(): void
    {
        $voucher = $this->createFiscalVoucher('FAC');
        $user = $this->createAndAuthenticateUser();

        $payload = [
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'notes' => 'Test items vacíos',
            'client_id' => $voucher->client_id,
            'currency' => 'ARS',
            'client_name' => 'Test',
            'items' => [], // Items vacíos
        ];

        $response = $this->putJson("/api/vouchers/{$voucher->id}", $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items']);
    }

    #[Test]
    public function test_credit_debit_voucher_update_with_associations(): void
    {
        // Crear voucher original para asociar
        $originalVoucher = $this->createFiscalVoucher('FAC');
        $originalVoucher->update(['status' => 'issued']);

        // Crear voucher de crédito/débito
        $voucher = $this->createFiscalVoucher('N/C');
        $user = $this->createAndAuthenticateUser();

        // Crear TaxRate para los items
        $taxRate = $this->createTaxRate();

        $payload = [
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'notes' => 'Actualización con asociaciones',
            'client_id' => $voucher->client_id,
            'currency' => 'ARS',
            'client_name' => 'Test',
            'items' => [
                [
                    'description' => 'Ajuste de factura',
                    'quantity' => 1,
                    'unit_price' => 50000,
                    'tax_rate_id' => $taxRate->id,
                ],
            ],
            'associated_voucher_ids' => [$originalVoucher->id],
        ];

        $response = $this->putJson("/api/vouchers/{$voucher->id}", $payload);

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $voucher->id,
            'notes' => 'Actualización con asociaciones',
        ], 'data');

        // Verificar que las asociaciones se guardaron
        $this->assertDatabaseHas('voucher_associations', [
            'voucher_id' => $voucher->id,
            'associated_voucher_id' => $originalVoucher->id,
        ]);
    }

    #[Test]
    public function test_fiscal_voucher_cannot_remove_all_items(): void
    {
        $voucher = $this->createFiscalVoucher('FAC');

        // Crear TaxRate para los items
        $taxRate = $this->createTaxRate();

        // Agregar items iniciales con todos los campos requeridos
        \App\Models\VoucherItem::create([
            'voucher_id' => $voucher->id,
            'type' => 'service',
            'description' => 'Item original',
            'quantity' => 1,
            'unit_price' => 1000,
            'subtotal' => 1000,
            'vat_amount' => 0,
            'tax_rate_id' => null,
            'subtotal_with_vat' => 1000,
        ]);

        $user = $this->createAndAuthenticateUser();

        $payload = [
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'notes' => 'Test sin items',
            'client_id' => $voucher->client_id,
            'currency' => 'ARS',
            'client_name' => 'Test',
            // No enviar items para intentar eliminarlos todos
        ];

        $response = $this->putJson("/api/vouchers/{$voucher->id}", $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items']);

        // Verificar que el item original sigue existiendo
        $this->assertDatabaseHas('voucher_items', [
            'voucher_id' => $voucher->id,
            'description' => 'Item original',
        ]);
    }

    // ============================================================================
    // TESTS PARA RECIBOS (RCB, RPG)
    // ============================================================================

    #[Test]
    public function test_receipt_voucher_update_with_valid_payments(): void
    {
        $voucher = $this->createReceiptVoucher('RCB');
        $user = $this->createAndAuthenticateUser();

        // Crear cuenta de caja primero
        $cashAccount = \App\Models\CashAccount::create([
            'name' => 'Caja Principal',
            'type' => 'cash',
            'currency' => 'ARS',
            'is_active' => true,
        ]);

        // Crear método de pago
        $paymentMethod = \App\Models\PaymentMethod::create([
            'name' => 'Efectivo',
            'code' => 'CASH',
            'default_cash_account_id' => $cashAccount->id,
        ]);

        $payload = [
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'notes' => 'Actualización con pagos',
            'client_id' => $voucher->client_id,
            'currency' => 'ARS',
            'client_name' => 'Test',
            'payments' => [
                [
                    'payment_method_id' => $paymentMethod->id,
                    'amount' => 50000,
                    'reference' => 'Pago parcial',
                ],
                [
                    'payment_method_id' => $paymentMethod->id,
                    'amount' => 50000,
                    'reference' => 'Pago restante',
                ],
            ],
        ];

        $response = $this->putJson("/api/vouchers/{$voucher->id}", $payload);

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $voucher->id,
            'notes' => 'Actualización con pagos',
        ], 'data');

        // Verificar que los pagos se guardaron
        $this->assertDatabaseHas('voucher_payments', [
            'voucher_id' => $voucher->id,
            'payment_method_id' => $paymentMethod->id,
            'amount' => 50000,
            'reference' => 'Pago parcial',
        ]);

        $this->assertDatabaseHas('voucher_payments', [
            'voucher_id' => $voucher->id,
            'payment_method_id' => $paymentMethod->id,
            'amount' => 50000,
            'reference' => 'Pago restante',
        ]);
    }

    #[Test]
    public function test_receipt_voucher_payment_validation(): void
    {
        $voucher = $this->createReceiptVoucher('RCB');
        $user = $this->createAndAuthenticateUser();

        // Crear cuenta de caja primero
        $cashAccount = \App\Models\CashAccount::create([
            'name' => 'Caja Principal',
            'type' => 'cash',
            'currency' => 'ARS',
            'is_active' => true,
        ]);

        // Crear método de pago válido para el test
        $paymentMethod = \App\Models\PaymentMethod::create([
            'name' => 'Efectivo',
            'code' => 'CASH',
            'default_cash_account_id' => $cashAccount->id,
        ]);

        $payload = [
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'notes' => 'Test validación pagos',
            'client_id' => $voucher->client_id,
            'currency' => 'ARS',
            'client_name' => 'Test',
            'payments' => [
                [
                    // Sin payment_method_id (requerido)
                    'amount' => 50000,
                ],
            ],
        ];

        $response = $this->putJson("/api/vouchers/{$voucher->id}", $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payments.0.payment_method_id']);
    }

    #[Test]
    public function test_receipt_voucher_update_applications(): void
    {
        $voucher = $this->createReceiptVoucher('RCB');
        $user = $this->createAndAuthenticateUser();

        // Crear voucher aplicable
        $applicableVoucher = $this->createFiscalVoucher('FAC');
        $applicableVoucher->update(['status' => 'issued']);

        $payload = [
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'notes' => 'Actualización con aplicaciones',
            'client_id' => $voucher->client_id,
            'currency' => 'ARS',
            'client_name' => 'Test',
            'applications' => [
                [
                    'applied_to_id' => $applicableVoucher->id,
                    'amount' => 25000,
                ],
            ],
        ];

        $response = $this->putJson("/api/vouchers/{$voucher->id}", $payload);

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $voucher->id,
            'notes' => 'Actualización con aplicaciones',
        ], 'data');

        // Verificar que las aplicaciones se guardaron
        $this->assertDatabaseHas('voucher_applications', [
            'voucher_id' => $voucher->id,
            'applied_to_id' => $applicableVoucher->id,
            'amount' => 25000,
        ]);
    }

    // ============================================================================
    // TESTS PARA LIQUIDACIONES (LIQ)
    // ============================================================================

    #[Test]
    public function test_liquidation_voucher_update_with_amount_items(): void
    {
        $voucher = $this->createLiquidationVoucher();
        $user = $this->createAndAuthenticateUser();

        $payload = [
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'notes' => 'Actualización liquidación',
            'client_id' => $voucher->client_id,
            'currency' => 'ARS',
            'client_name' => 'Test',
            'items' => [
                [
                    'description' => 'Comisión de administración',
                    'amount' => 15000,
                ],
                [
                    'description' => 'Gastos de mantenimiento',
                    'amount' => 8000,
                ],
            ],
        ];

        $response = $this->putJson("/api/vouchers/{$voucher->id}", $payload);

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $voucher->id,
            'notes' => 'Actualización liquidación',
        ], 'data');

        // Verificar que los items se guardaron con amount
        $this->assertDatabaseHas('voucher_items', [
            'voucher_id' => $voucher->id,
            'description' => 'Comisión de administración',
        ]);

        $this->assertDatabaseHas('voucher_items', [
            'voucher_id' => $voucher->id,
            'description' => 'Gastos de mantenimiento',
        ]);
    }

    #[Test]
    public function test_liquidation_voucher_reject_quantity_tax_rate(): void
    {
        $voucher = $this->createLiquidationVoucher();
        $user = $this->createAndAuthenticateUser();

        // Crear TaxRate para el test
        $taxRate = $this->createTaxRate();

        $payload = [
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'notes' => 'Test validación LIQ',
            'client_id' => $voucher->client_id,
            'currency' => 'ARS',
            'client_name' => 'Test',
            'items' => [
                [
                    'description' => 'Item con campos inválidos',
                    'amount' => 10000,
                    'quantity' => 1, // No permitido en LIQ
                    'unit_price' => 10000, // No permitido en LIQ
                    'tax_rate_id' => $taxRate->id, // No permitido en LIQ
                ],
            ],
        ];

        $response = $this->putJson("/api/vouchers/{$voucher->id}", $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.quantity', 'items.0.unit_price', 'items.0.tax_rate_id']);
    }

    // ============================================================================
    // TESTS PARA COBROS (COB)
    // ============================================================================

    #[Test]
    public function test_collection_voucher_update_with_items(): void
    {
        $voucher = $this->createCollectionVoucher();
        $user = $this->createAndAuthenticateUser();

        // Crear TaxRate para los items
        $taxRate = $this->createTaxRate();

        $payload = [
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'notes' => 'Actualización cobro',
            'contract_id' => $voucher->contract_id,
            'client_id' => $voucher->client_id,
            'currency' => 'ARS',
            'client_name' => 'Test',
            'items' => [
                [
                    'description' => 'Alquiler manual',
                    'quantity' => 1,
                    'unit_price' => 100000,
                    'tax_rate_id' => $taxRate->id,
                ],
                [
                    'description' => 'Gastos de limpieza',
                    'quantity' => 1,
                    'unit_price' => 5000,
                    'tax_rate_id' => null,
                ],
            ],
        ];

        $response = $this->putJson("/api/vouchers/{$voucher->id}", $payload);

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $voucher->id,
            'notes' => 'Actualización cobro',
        ], 'data');

        // Verificar que los items se guardaron
        $this->assertDatabaseHas('voucher_items', [
            'voucher_id' => $voucher->id,
            'description' => 'Alquiler manual',
            'quantity' => 1,
            'unit_price' => 100000,
        ]);
    }

    #[Test]
    public function test_collection_voucher_contract_id_required(): void
    {
        $voucher = $this->createCollectionVoucher();
        $user = $this->createAndAuthenticateUser();

        $payload = [
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'notes' => 'Test sin contract_id',
            'client_id' => $voucher->client_id,
            'currency' => 'ARS',
            'client_name' => 'Test',
            // Sin contract_id (requerido para COB)
        ];

        $response = $this->putJson("/api/vouchers/{$voucher->id}", $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['contract_id']);
    }

    #[Test]
    public function test_collection_voucher_client_must_be_tenant(): void
    {
        $voucher = $this->createCollectionVoucher();
        $user = $this->createAndAuthenticateUser();

        // Crear cliente que no es tenant del contrato
        $documentType = DocumentType::where('name', 'DNI')->first() ?: DocumentType::create(['name' => 'DNI2', 'is_default' => false]);
        $taxCondition = TaxCondition::where('name', 'Consumidor Final')->first() ?: TaxCondition::create(['name' => 'Consumidor Final2', 'is_default' => false]);

        $nonTenantClient = Client::create([
            'type' => 'individual',
            'name' => 'No Tenant',
            'last_name' => 'Client',
            'email' => 'notenant@test.com',
            'document_type_id' => $documentType->id,
            'document_number' => '99999999',
            'tax_condition_id' => $taxCondition->id,
        ]);

        $payload = [
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'notes' => 'Test cliente no tenant',
            'contract_id' => $voucher->contract_id,
            'client_id' => $nonTenantClient->id, // Cliente que no es tenant
            'currency' => 'ARS',
            'client_name' => $nonTenantClient->name,
        ];

        $response = $this->putJson("/api/vouchers/{$voucher->id}", $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['client_id']);
    }

    // ============================================================================
    // TESTS TÉCNICOS ADICIONALES
    // ============================================================================

    #[Test]
    public function test_partial_update_preserve_existing_items_payments(): void
    {
        $voucher = $this->createFiscalVoucher('FAC');

        // Crear TaxRate para los items
        $taxRate = $this->createTaxRate();

        // Crear items existentes con todos los campos requeridos
        \App\Models\VoucherItem::create([
            'voucher_id' => $voucher->id,
            'type' => 'service',
            'description' => 'Item existente',
            'quantity' => 1,
            'unit_price' => 1000,
            'subtotal' => 1000,
            'vat_amount' => 0,
            'tax_rate_id' => null,
            'subtotal_with_vat' => 1000,
        ]);

        $user = $this->createAndAuthenticateUser();

        $payload = [
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'notes' => 'Actualización parcial',
            'client_id' => $voucher->client_id,
            'currency' => 'ARS',
            'client_name' => 'Test Client',
            'items' => [
                [
                    'description' => 'Nuevo item',
                    'quantity' => 1,
                    'unit_price' => 2000,
                    'tax_rate_id' => $taxRate->id,
                ],
            ],
        ];

        $response = $this->putJson("/api/vouchers/{$voucher->id}", $payload);

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $voucher->id,
            'notes' => 'Actualización parcial',
        ], 'data');

        // Verificar que el nuevo item se agregó
        $this->assertDatabaseHas('voucher_items', [
            'voucher_id' => $voucher->id,
            'description' => 'Nuevo item',
        ]);
    }

    #[Test]
    public function test_update_replace_all_items(): void
    {
        $voucher = $this->createFiscalVoucher('FAC');

        // Crear TaxRate para los items
        $taxRate = $this->createTaxRate();

        // Crear items existentes con todos los campos requeridos
        \App\Models\VoucherItem::create([
            'voucher_id' => $voucher->id,
            'type' => 'service',
            'description' => 'Item a reemplazar',
            'quantity' => 1,
            'unit_price' => 1000,
            'subtotal' => 1000,
            'vat_amount' => 0,
            'tax_rate_id' => null,
            'subtotal_with_vat' => 1000,
        ]);

        $user = $this->createAndAuthenticateUser();

        $payload = [
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'notes' => 'Reemplazo de items',
            'client_id' => $voucher->client_id,
            'currency' => 'ARS',
            'client_name' => 'Test',
            'items' => [
                [
                    'description' => 'Nuevo item 1',
                    'quantity' => 2,
                    'unit_price' => 2000,
                    'tax_rate_id' => $taxRate->id,
                ],
                [
                    'description' => 'Nuevo item 2',
                    'quantity' => 1,
                    'unit_price' => 3000,
                    'tax_rate_id' => null,
                ],
            ],
        ];

        $response = $this->putJson("/api/vouchers/{$voucher->id}", $payload);

        $response->assertOk();

        // Verificar que el item original fue eliminado
        $this->assertDatabaseMissing('voucher_items', [
            'voucher_id' => $voucher->id,
            'description' => 'Item a reemplazar',
        ]);

        // Verificar que los nuevos items se guardaron
        $this->assertDatabaseHas('voucher_items', [
            'voucher_id' => $voucher->id,
            'description' => 'Nuevo item 1',
        ]);

        $this->assertDatabaseHas('voucher_items', [
            'voucher_id' => $voucher->id,
            'description' => 'Nuevo item 2',
        ]);
    }

    #[Test]
    public function test_update_client_info_without_changing_client_id(): void
    {
        $voucher = $this->createBasicVoucher();
        $originalClientId = $voucher->client_id;
        $user = $this->createAndAuthenticateUser();

        // Crear TaxRate para los items
        $taxRate = $this->createTaxRate();

        $payload = [
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'notes' => 'Actualización info cliente',
            'client_id' => $originalClientId, // Mantener el mismo client_id
            'currency' => 'ARS',
            'client_name' => 'Cliente Actualizado',
            'client_address' => 'Nueva dirección',
            'client_document_type_name' => 'DNI',
            'client_document_number' => '12345678',
            'items' => [
                [
                    'description' => 'Servicio de alquiler',
                    'quantity' => 1,
                    'unit_price' => 100000,
                    'tax_rate_id' => $taxRate->id,
                ],
            ],
        ];

        $response = $this->putJson("/api/vouchers/{$voucher->id}", $payload);

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $voucher->id,
            'notes' => 'Actualización info cliente',
        ], 'data');

        // Verificar que el client_id no cambió
        $this->assertDatabaseHas('vouchers', [
            'id' => $voucher->id,
            'client_id' => $originalClientId,
            'client_name' => 'Cliente Actualizado',
            'client_address' => 'Nueva dirección',
        ]);
    }

    // ============================================================================
    // MÉTODOS AUXILIARES PARA CREAR VOUCHERS ESPECÍFICOS
    // ============================================================================

    private function createFiscalVoucher(string $type): Voucher
    {
        $voucher = $this->createBasicVoucher();
        $voucher->update([
            'voucher_type_short_name' => $type,
        ]);
        return $voucher;
    }

    private function createReceiptVoucher(string $type): Voucher
    {
        $voucher = $this->createBasicVoucher();
        $voucher->update([
            'voucher_type_short_name' => $type,
        ]);
        return $voucher;
    }

    private function createLiquidationVoucher(): Voucher
    {
        $voucher = $this->createBasicVoucher();
        $voucher->update([
            'voucher_type_short_name' => 'LIQ',
        ]);
        return $voucher;
    }

    private function createCollectionVoucher(): Voucher
    {
        $voucher = $this->createBasicVoucher();
        $voucher->update([
            'voucher_type_short_name' => 'COB',
        ]);
        return $voucher;
    }

    private function createAndAuthenticateUser(): \App\Models\User
    {
        $user = \App\Models\User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($user);
        return $user;
    }

    private function createTaxRate(): \App\Models\TaxRate
    {
        // Buscar TaxRate existente o crear uno nuevo
        $taxRate = \App\Models\TaxRate::where('name', 'IVA 21%')->first();

        if (!$taxRate) {
            $taxRate = \App\Models\TaxRate::create([
                'name' => 'IVA 21%',
                'rate' => 21.00,
                'is_default' => true,
                'included_in_vat_detail' => true,
            ]);
        }

        return $taxRate;
    }

    private function createPropertyType(): \App\Models\PropertyType
    {
        // Buscar PropertyType existente o crear uno nuevo
        $propertyType = \App\Models\PropertyType::where('name', 'Casa')->first();

        if (!$propertyType) {
            $propertyType = \App\Models\PropertyType::create([
                'name' => 'Casa',
            ]);
        }

        return $propertyType;
    }

    private function createCountry(): \App\Models\Country
    {
        // Buscar Country existente o crear uno nuevo
        $country = \App\Models\Country::where('name', 'Argentina')->first();

        if (!$country) {
            $country = \App\Models\Country::create([
                'name' => 'Argentina',
                'is_default' => false,
            ]);
        }

        return $country;
    }

    private function createState(\App\Models\Country $country): \App\Models\State
    {
        // Buscar State existente o crear uno nuevo
        $state = \App\Models\State::where('name', 'Buenos Aires')->first();

        if (!$state) {
            $state = \App\Models\State::create([
                'name' => 'Buenos Aires',
                'country_id' => $country->id,
                'is_default' => false,
            ]);
        }

        return $state;
    }

    private function createCity(\App\Models\State $state): \App\Models\City
    {
        // Buscar City existente o crear una nueva
        $city = \App\Models\City::where('name', 'Bahía Blanca')->first();

        if (!$city) {
            $city = \App\Models\City::create([
                'name' => 'Bahía Blanca',
                'state_id' => $state->id,
                'is_default' => false,
            ]);
        }

        return $city;
    }

    private function createNeighborhood(\App\Models\City $city): \App\Models\Neighborhood
    {
        // Buscar Neighborhood existente o crear uno nuevo
        $neighborhood = \App\Models\Neighborhood::where('name', 'Centro')->first();

        if (!$neighborhood) {
            $neighborhood = \App\Models\Neighborhood::create([
                'name' => 'Centro',
                'city_id' => $city->id,
                'is_default' => false,
            ]);
        }

        return $neighborhood;
    }
}
