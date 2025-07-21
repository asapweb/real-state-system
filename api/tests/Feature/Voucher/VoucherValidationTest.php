<?php

namespace Tests\Feature\Voucher;

use App\Models\Voucher;
use App\Models\VoucherItem;
use App\Models\VoucherPayment;
use App\Models\VoucherAssociation;
use App\Models\VoucherApplication;
use App\Models\VoucherType;
use App\Services\VoucherCalculationService;
use App\Services\VoucherValidatorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\DocumentType;
use App\Models\TaxCondition;
use App\Models\CivilStatus;
use App\Models\Nationality;

class VoucherValidationTest extends TestCase
{
    use DatabaseTransactions;

    protected VoucherValidatorService $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new VoucherValidatorService(
            app(VoucherCalculationService::class)
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fac_passes_with_valid_item(): void
    {
        $voucher = Voucher::factory()->fac()->draft()->create();

        VoucherItem::factory()->for($voucher)->create([
            'description' => 'Alquiler julio',
            'quantity' => 1,
            'unit_price' => 100000,
        ]);

        app(VoucherCalculationService::class)->calculateVoucher($voucher);
        $this->validator->validateBeforeIssue($voucher);

        $this->assertTrue(true);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function receipt_passes_with_exact_payment(): void
    {
        $voucher = Voucher::factory()->receipt()->draft()->create();

        VoucherItem::factory()->for($voucher)->create([
            'unit_price' => 50000,
        ]);


        VoucherPayment::factory()->for($voucher)->create([
            'amount' => $voucher->total,
        ]);
        app(VoucherCalculationService::class)->calculateVoucher($voucher);

        $this->validator->validateBeforeIssue($voucher);
        $this->assertTrue(true);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function credit_note_passes_with_negative_total_and_item(): void
    {
        $voucher = Voucher::factory()->creditNote()->draft()->create();

        VoucherItem::factory()->for($voucher)->create([
            'unit_price' => -20000,
        ]);

        app(VoucherCalculationService::class)->calculateVoucher($voucher);
        $this->validator->validateBeforeIssue($voucher);

        $this->assertTrue(true);
    }

    // ✅ Verifica que la nota de crédito falle si se asocia a otra nota de crédito.
    #[\PHPUnit\Framework\Attributes\Test]
    public function credit_note_fails_if_associates_to_another_credit_note(): void
    {
        $target = Voucher::factory()->creditNote()->create();

        $voucher = Voucher::factory()->creditNote()->draft()->create([
            'total' => -1000,
        ]);

        VoucherAssociation::factory()->for($voucher)->create([
            'associated_voucher_id' => $target->id,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->expectExceptionMessage('No se puede asociar una nota de crédito a otra nota de crédito.');
        $this->validator->validateBeforeIssue($voucher);
    }


    #[\PHPUnit\Framework\Attributes\Test]
    public function debit_note_passes_with_item_and_association(): void
    {
        $associated = Voucher::factory()->fac()->create();

        $voucher = Voucher::factory()->debitNote()->draft()->create();

        VoucherItem::factory()->for($voucher)->create([
            'unit_price' => 30000,
        ]);

        VoucherAssociation::factory()->for($voucher)->create([
            'associated_voucher_id' => $associated->id,
        ]);

        app(VoucherCalculationService::class)->calculateVoucher($voucher);
        $this->validator->validateBeforeIssue($voucher);

        $this->assertTrue(true);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function debit_note_fails_with_multiple_associations(): void
    {
        $voucher = Voucher::factory()->debitNote()->draft()->create([
            'total' => 1000,
        ]);

        VoucherItem::factory()->for($voucher)->create(['unit_price' => 1000]);

        $fac1 = Voucher::factory()->fac()->create();
        $fac2 = Voucher::factory()->fac()->create();

        VoucherAssociation::factory()->for($voucher)->create(['associated_voucher_id' => $fac1->id]);
        VoucherAssociation::factory()->for($voucher)->create(['associated_voucher_id' => $fac2->id]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->expectExceptionMessage('La nota de débito debe estar asociada a un solo comprobante.');
        $this->validator->validateBeforeIssue($voucher);
    }


    #[\PHPUnit\Framework\Attributes\Test]
    public function cob_passes_with_valid_items(): void
    {
        $voucher = Voucher::factory()->cob()->draft()->create();

        VoucherItem::factory()->for($voucher)->create([
            'type' => 'rent',
            'unit_price' => 60000,
        ]);

        app(VoucherCalculationService::class)->calculateVoucher($voucher);
        $this->validator->validateBeforeIssue($voucher);

        $this->assertTrue(true);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function liq_passes_with_owner_and_item(): void
    {
        $voucher = Voucher::factory()->liq()->draft()->create();

        VoucherItem::factory()->for($voucher)->create([
            'description' => 'Liquidación julio',
            'unit_price' => 25000,
        ]);

        app(VoucherCalculationService::class)->calculateVoucher($voucher);
        $this->validator->validateBeforeIssue($voucher);

        $this->assertTrue(true);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function receipt_passes_with_zero_total_and_exact_compensation(): void
    {
        $voucher = Voucher::factory()->receipt()->draft()->create([
            'total' => 0,
        ]);

        // Simulamos: FACTURA (id = A), N/C (id = B)
        $fac = Voucher::factory()->fac()->create([
            'total' => 666,
        ]);

        $nc = Voucher::factory()->creditNote()->create([
            'total' => -666,
        ]);

        VoucherApplication::factory()->for($voucher)->create([
            'applied_to_id' => $fac->id,
            'amount' => 666,
        ]);

        VoucherApplication::factory()->for($voucher)->create([
            'applied_to_id' => $nc->id,
            'amount' => -666,
        ]);

        // No payments ni items
        $this->validator->validateBeforeIssue($voucher);
        $this->assertTrue(true);
    }
    #[\PHPUnit\Framework\Attributes\Test]
    public function receipt_fails_if_zero_total_does_not_match_applications(): void
    {
        $voucher = Voucher::factory()->receipt()->draft()->create(['total' => 0]);

        $fac = Voucher::factory()->fac()->create(['total' => 666]);
        $nc = Voucher::factory()->creditNote()->create(['total' => -500]);

        VoucherApplication::factory()->for($voucher)->create([
            'applied_to_id' => $fac->id,
            'amount' => 666,
        ]);

        VoucherApplication::factory()->for($voucher)->create([
            'applied_to_id' => $nc->id,
            'amount' => -500,
        ]);

        $this->assertSame(166.0, $voucher->applications->sum('amount'));
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->validator->validateBeforeIssue($voucher);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fac_fails_with_no_items(): void
    {
        $voucher = Voucher::factory()->fac()->draft()->create();
        $voucher->total = 1000;

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->validator->validateBeforeIssue($voucher);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function credit_note_fails_with_positive_total(): void
    {
        $voucher = Voucher::factory()->creditNote()->draft()->create([
            'total' => 1000,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->validator->validateBeforeIssue($voucher);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function credit_note_fails_with_no_items_or_associations(): void
    {
        $voucher = Voucher::factory()->creditNote()->draft()->create([
            'total' => -1000,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->validator->validateBeforeIssue($voucher);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function debit_note_fails_with_no_association(): void
    {
        $voucher = Voucher::factory()->debitNote()->draft()->create([
            'total' => 1000,
        ]);

        VoucherItem::factory()->for($voucher)->create([
            'unit_price' => 1000,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->validator->validateBeforeIssue($voucher);
    }

    // ✅ Verifica que el recibo falle si tiene total > 0 pero no tiene pagos registrados.
    #[\PHPUnit\Framework\Attributes\Test]
    public function receipt_fails_with_total_and_no_payments(): void
    {
        $voucher = Voucher::factory()->receipt()->draft()->create([
            'total' => 1000,
        ]);

        // Mock para evitar que se recalculen los pagos (vacío a propósito)
        $mockCalculation = $this->createMock(VoucherCalculationService::class);
        $mockCalculation->expects($this->once())
            ->method('calculateVoucher')
            ->willReturnCallback(function ($voucher) {
                // No modifica total ni payments
            });

        $validator = new VoucherValidatorService($mockCalculation);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->expectExceptionMessage('Debe registrar al menos una forma de pago si el total es mayor a cero.');
        $validator->validateBeforeIssue($voucher);
    }

    // ✅ Verifica que el recibo falle si el total no coincide con la suma de los pagos.
    #[\PHPUnit\Framework\Attributes\Test]
    public function receipt_fails_if_total_does_not_match_payments(): void
    {
        $voucher = Voucher::factory()->receipt()->draft()->create([
            'total' => 1000,
        ]);

        VoucherPayment::factory()->for($voucher)->create([
            'amount' => 800,
        ]);

        // Mock para evitar que el validador recalule el total con los pagos
        $mockCalculation = $this->createMock(VoucherCalculationService::class);
        $mockCalculation->expects($this->once())
            ->method('calculateVoucher')
            ->willReturnCallback(function ($voucher) {
                // No modifica el total
            });

        $validator = new VoucherValidatorService($mockCalculation);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->expectExceptionMessage('El total del recibo no coincide con la suma de los pagos.');
        $validator->validateBeforeIssue($voucher);
    }


    #[\PHPUnit\Framework\Attributes\Test]
    public function receipt_fails_if_applies_to_other_receipt(): void
    {
        $voucher = Voucher::factory()->receipt()->draft()->create([
            'total' => 1000,
        ]);

        VoucherPayment::factory()->for($voucher)->create([
            'amount' => 1000,
        ]);

        $target = Voucher::factory()->receipt()->create();

        VoucherApplication::factory()->for($voucher)->create([
            'applied_to_id' => $target->id,
            'amount' => 1000,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->validator->validateBeforeIssue($voucher);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function receipt_fails_if_total_differs_from_sum_of_payments(): void
    {
        $voucher = Voucher::factory()->receipt()->draft()->create();

        VoucherPayment::factory()->for($voucher)->create([
            'amount' => 800,
        ]);

        // Stub para evitar que se recalculen los totales en la validación
        $mockCalculation = $this->createMock(VoucherCalculationService::class);
        $mockCalculation->expects($this->once())
            ->method('calculateVoucher')
            ->willReturnCallback(function ($voucher) {
                // No modifica $voucher->total
            });

        $validator = new VoucherValidatorService($mockCalculation);

        // Forzamos inconsistencia
        $voucher->total = 1000;
        $voucher->save();

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->expectExceptionMessage('El total del recibo no coincide con la suma de los pagos.');
        $validator->validateBeforeIssue($voucher);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function receipt_fails_if_has_total_but_no_payments(): void
    {
        $voucher = Voucher::factory()->receipt()->draft()->create();

        // Ítem crea un total > 0
        VoucherItem::factory()->for($voucher)->create([
            'unit_price' => 5000,
        ]);

        // Stub para evitar que recalcule
        $mockCalculation = $this->createMock(VoucherCalculationService::class);
        $mockCalculation->expects($this->once())
            ->method('calculateVoucher')
            ->willReturnCallback(function ($voucher) {
                // No modifica total ni payments
            });

        $validator = new VoucherValidatorService($mockCalculation);

        $voucher->total = 5000;
        $voucher->save();

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->expectExceptionMessage('Debe registrar al menos una forma de pago si el total es mayor a cero.');
        $validator->validateBeforeIssue($voucher);
    }

}
