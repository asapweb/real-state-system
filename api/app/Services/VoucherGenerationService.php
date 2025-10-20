<?php

namespace App\Services;

use App\Enums\ContractExpenseStatus;
use App\Enums\VoucherStatus;
use App\Models\Contract;
use App\Models\Booklet;
use App\Models\ContractExpense;
use App\Models\Voucher;
use App\Models\VoucherItem;
use App\Models\VoucherType;
use App\Exceptions\PendingAdjustmentException;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class VoucherGenerationService
{
    public function __construct(
        protected ContractBillingService $billingService,
        protected VoucherService $voucherService
    ) {}

    /**
     * Generación manual desde el Editor de Cobranzas.
     * Reemplaza los vouchers existentes para ese contrato + período.
     */
    public function generateFromEditor(Contract $contract, string $period, array $items): void
    {
        \Log::info('generateFromEditor');
        \Log::info('debug', ['items' => $items]);
        DB::transaction(function () use ($contract, $period, $items) {
            $grouped = collect($items)->groupBy(function ($item) use ($contract) {
                return $item['id'] === 'rent'
                    ? $contract->currency
                    : ContractExpense::findOrFail((int) str_replace('expense-', '', $item['id']))->currency;
            });
            foreach ($grouped as $currency => $groupItems) {
                $voucherItems = [];
                $selectedExpenseIds = [];
                \Log::info('--------------------------------');
                \Log::info('groupItems',['groupItems' => $groupItems]);
                foreach ($groupItems as $item) {
                    \Log::info('item',['item' => $item]);
                    if ($item['type'] === 'rent') {
                        $voucherItems[] = [
                            'description' => $item['description'],
                            'quantity' => 1,
                            'unit_price' => $item['amount'],
                            'type' => 'rent',
                        ];
                    } elseif ($item['type'] === 'expense') {
                        $expenseId = (int) str_replace('expense-', '', $item['id']);
                        \Log::info('expenseId',['expenseId' => $item['type']]);
                        \Log::info('expenseId',['expenseId' => $expenseId]);
                        $expense = ContractExpense::findOrFail($expenseId);

                        $voucherItems[] = [
                            'description' => $item['description'],
                            'quantity' => 1,
                            'unit_price' => $item['amount'],
                            'type' => 'expense',
                        ];

                        $selectedExpenseIds[] = $expense->id;
                    }
                }
                \Log::info('========================================');
                \Log::info('voucherItems',['voucherItems' => $voucherItems]);
                \Log::info('groupItems',['groupItems' => $groupItems]);
                // $voucherType = VoucherType::where('short_name', 'COB')->firstOrFail();
                // $booklet = $voucherType->booklets()->where('currency', $currency)->firstOrFail();
                $booklet = $contract->collectionBooklet;

                $existingVoucher = $contract->vouchers()
                    ->where('period', $period)
                    ->where('currency', $currency)
                    ->where('status', VoucherStatus::Draft->value)
                    ->where('generated_from_collection', true)
                    ->first();

                if ($existingVoucher) {
                    // 🔄 Eliminar gastos actualmente vinculados a este voucher
                    ContractExpense::where('voucher_id', $existingVoucher->id)->update([
                        'voucher_id' => null,
                        'included_in_voucher' => false,
                        'status' => ContractExpenseStatus::PENDING,
                    ]);

                    // 🔄 Actualizar el voucher existente
                    app(VoucherService::class)->updateFromArray($existingVoucher, [
                        'booklet_id' => $existingVoucher->booklet_id,
                        'contract_id' => $existingVoucher->contract_id,
                        'client_id' => $existingVoucher->client_id,
                        'client_name' => $existingVoucher->client_name,
                        'client_address' => $existingVoucher->client_address,
                        'currency' => $existingVoucher->currency,
                        'period' => $existingVoucher->period,
                        'issue_date' => $existingVoucher->issue_date?->toDateString() ?? now()->toDateString(),
                        'due_date' => $existingVoucher->due_date?->toDateString() ?? now()->toDateString(),
                        'items' => $voucherItems,
                        'notes' => $existingVoucher->notes,
                        'generated_from_collection' => true,
                    ]);

                    $voucher = $existingVoucher;
                } else {
                    \Log::info('booklet',['booklet' => $booklet->voucherType->short_name]);
                    \Log::info('voucherItems',['voucherItems' => $voucherItems]);
                    // 🆕 Crear nuevo voucher

                    $client = $contract->mainTenant->client;
                    $voucher = app(VoucherService::class)->createFromArray([
                        'booklet_id' => $booklet->id,
                        'voucher_type_id' => $booklet->voucher_type_id,
                        'voucher_type_short_name' => $booklet->voucherType->short_name,
                        'voucher_type_letter' => $booklet->voucherType->letter,
                        'client_name' => $client->full_name,
                        'client_address' => $client->address,
                        'contract_id' => $contract->id,
                        'client_id' => $contract->mainTenant->client_id,
                        'currency' => $currency,
                        'period' => $period,
                        'issue_date' => now()->toDateString(),
                        'due_date' => now()->toDateString(),
                        'items' => $voucherItems,
                        'generated_from_collection' => true,
                    ]);
                }

                // ✅ Marcar gastos seleccionados como incluidos
                if (!empty($selectedExpenseIds)) {
                    ContractExpense::whereIn('id', $selectedExpenseIds)->update([
                        'voucher_id' => $voucher->id,
                        'included_in_voucher' => true,
                        'status' => ContractExpenseStatus::BILLED,
                    ]);
                }
            }

            // Eliminar vouchers que no corresponden a las monedas seleccionadas
            $currenciesFromForm = collect($items)->map(function ($item) use ($contract) {
                return $item['id'] === 'rent'
                    ? $contract->currency
                    : ContractExpense::findOrFail((int) str_replace('expense-', '', $item['id']))->currency;
            })->unique();

            $existingVouchers = $contract->vouchers()
                ->where('period', $period)
                ->where('status', VoucherStatus::Draft->value)
                ->where('generated_from_collection', true)
                ->get();

            $orphanedVouchers = $existingVouchers->filter(function ($voucher) use ($currenciesFromForm) {
                return ! $currenciesFromForm->contains($voucher->currency);
            });

            foreach ($orphanedVouchers as $voucher) {
                // 🔄 Desvincular gastos
                ContractExpense::where('voucher_id', $voucher->id)->update([
                    'voucher_id' => null,
                    'included_in_voucher' => false,
                    'status' => ContractExpenseStatus::PENDING,
                ]);

                // ❌ Eliminar el voucher
                $voucher->delete();
            }
        });
    }

    /**
     * Genera la cobranza mensual de un contrato para un período específico.
     *
     * @throws PendingAdjustmentException
     */
    public function generateForMonth(Contract $contract, string|Carbon $period): void
    {
        $period = normalizePeriodOrFail($period);

        // 1️⃣ Obtener cálculo previo (renta, gastos, ajuste pendiente)
        $billing = $this->billingService->getBillingPreview($contract, $period);

        // 2️⃣ Validar ajuste pendiente
        if ($billing['pending_adjustment']) {
            throw new PendingAdjustmentException(
                "El contrato {$contract->id} tiene un ajuste pendiente para {$period->format('Y-m')}."
            );
        }

        // 3️⃣ Validar continuidad de cobranzas (no generar si hay gaps previos)
        $this->validateNoGap($contract, $period);

        // 4️⃣ Generar voucher principal (renta + gastos en moneda del contrato)
        $this->generatePrimaryVoucher($contract, $period, $billing);

        // 5️⃣ Generar vouchers adicionales para gastos en otras monedas
        $this->generateForeignCurrencyVouchers($contract, $period, $billing);
    }

    /**
     * Valida que no haya períodos previos sin generar.
     */
    protected function validateNoGap(Contract $contract, Carbon $period): void
    {
        $lastVoucherPeriod = $contract->vouchers()
            ->where('status', VoucherStatus::Issued->value)
            ->max('period');

        if ($lastVoucherPeriod) {
            $expectedNext = Carbon::parse($lastVoucherPeriod)->addMonth()->startOfMonth();
            if ($expectedNext->lt($period)) {
                throw new \RuntimeException(
                    "No se puede generar {$period->format('Y-m')}: hay períodos previos sin cobrar (última cobranza: {$lastVoucherPeriod})."
                );
            }
        }
    }

    /**
     * Genera el voucher principal en la moneda del contrato.
     */
    protected function generatePrimaryVoucher(Contract $contract, Carbon $period, array $billing): void
    {
        $voucherType = VoucherType::where('short_name', 'FAC')->firstOrFail();

        DB::transaction(function () use ($contract, $period, $billing, $voucherType) {
            // Crear voucher principal
            $voucher = Voucher::create([
                'voucher_type_id' => $voucherType->id,
                'voucher_type_short_name' => $voucherType->short_name,
                'voucher_type_letter' => 'X', // Factura X
                'contract_id' => $contract->id,
                'client_id' => $contract->mainTenant->client_id,
                'currency' => $contract->currency,
                'period' => $period,
                'status' => VoucherStatus::Draft->value,
                'issue_date' => now(),
                'due_date' => now()->endOfMonth(),
                'total' => 0,
            ]);

            // Agregar ítem de renta
            VoucherItem::create([
                'voucher_id' => $voucher->id,
                'type' => 'rent',
                'description' => "Alquiler " . $period->translatedFormat('F Y'),
                'quantity' => 1,
                'unit_price' => $billing['rent'],
                'subtotal' => $billing['rent'],
                'vat_amount' => 0,
                'subtotal_with_vat' => $billing['rent'],
                'meta' => ['source' => 'rent'],
            ]);

            $total = $billing['rent'];

            // Agregar gastos en la moneda del contrato
            foreach ($billing['expenses'] as $expense) {
                if ($expense['currency'] === $contract->currency) {
                    VoucherItem::create([
                        'voucher_id' => $voucher->id,
                        'type' => 'expense',
                        'description' => "Gastos " . $period->translatedFormat('F Y'),
                        'quantity' => 1,
                        'unit_price' => $expense['amount'],
                        'subtotal' => $expense['amount'],
                        'vat_amount' => 0,
                        'subtotal_with_vat' => $expense['amount'],
                        'meta' => ['source' => 'expense'],
                    ]);
                    $total += $expense['amount'];
                }
            }

            // Actualizar total del voucher
            $voucher->update(['total' => $total]);
        });
    }

    /**
     * Genera vouchers separados para gastos en monedas distintas.
     */
    protected function generateForeignCurrencyVouchers(Contract $contract, Carbon $period, array $billing): void
    {
        $voucherType = VoucherType::where('short_name', 'FAC')->firstOrFail();

        foreach ($billing['expenses'] as $expense) {
            if ($expense['currency'] !== $contract->currency) {
                DB::transaction(function () use ($contract, $period, $expense, $voucherType) {
                    $voucher = Voucher::create([
                        'voucher_type_id' => $voucherType->id,
                        'voucher_type_short_name' => $voucherType->short_name,
                        'voucher_type_letter' => 'X',
                        'contract_id' => $contract->id,
                        'client_id' => $contract->mainTenant->client_id,
                        'currency' => $expense['currency'],
                        'period' => $period,
                        'status' => VoucherStatus::Draft->value,
                        'issue_date' => now(),
                        'due_date' => now()->endOfMonth(),
                        'total' => $expense['amount'],
                    ]);

                    VoucherItem::create([
                        'voucher_id' => $voucher->id,
                        'type' => 'expense',
                        'description' => "Gastos " . $period->translatedFormat('F Y'),
                        'quantity' => 1,
                        'unit_price' => $expense['amount'],
                        'subtotal' => $expense['amount'],
                        'vat_amount' => 0,
                        'subtotal_with_vat' => $expense['amount'],
                        'meta' => ['source' => 'expense'],
                    ]);
                });
            }
        }
    }

    /**
     * Generación automática de vouchers desde contrato + período.
     * Se utiliza en procesos masivos o comandos.
     *
     * @param Contract $contract
     * @param Carbon $period
     * @param array $items  // [{id: string, amount: float}]
     * @return array<Voucher>
     */
    public function generateForPeriod(Contract $contract, Carbon $period, array $items): array
    {
        \Log::info("");
        \Log::info("------------------------------------------------------------------------------------------------------------------------------");
        \Log::info("");
        return DB::transaction(function () use ($contract, $period, $items) {
            // $voucherType = VoucherType::where('short_name', 'FAC')->firstOrFail();
            $voucherType = VoucherType::where('id', 17)->firstOrFail();
            $voucherService = app(VoucherService::class);

            // Obtener talonario interno FAC X
            // $booklet = Booklet::whereHas('voucherType', fn($q) =>
            //     $q->where('short_name', 'FAC')
            // )->where('internal', true)->firstOrFail();
            $booklet = Booklet::where('id', 17)->firstOrFail();
            \Log::info("items",  $items);
            // Agrupar ítems por moneda
            $itemsByCurrency = $this->groupItemsByCurrency($contract, $items);

            $vouchers = [];
            \Log::info("itemsByCurrency",  $itemsByCurrency);
            foreach ($itemsByCurrency as $currency => $currencyItems) {
                \Log::info("currency",  $currencyItems);
                // Buscar voucher borrador existente por contrato, período y moneda
                $voucher = $contract->vouchers()
                    ->where('voucher_type_id', $voucherType->id)
                    ->where('status', VoucherStatus::Draft->value)
                    ->whereDate('period', $period)
                    ->where('currency', $currency)
                    ->first();

                if ($voucher) {
                    \Log::info("voucher encontrado");
                    // 🔄 Actualizar ítems en el borrador existente
                    $voucher->items()->delete();


                    foreach ($currencyItems as $item) {
                        \Log::info("item",  $item);
                        \Log::info("Crearía item",  [
                            'type' => $this->resolveItemType($item['id']),
                            'description' => $item['description'],
                            'quantity' => 1,
                            'unit_price' => $item['amount'],
                            'subtotal' => $item['amount'],
                            'vat_amount' => 0,
                            'subtotal_with_vat' => $item['amount'],
                            'meta' => ['source' => 'generated'],
                        ]);
                        $voucher->items()->create([
                            'type' => $this->resolveItemType($item['id']),
                            'description' => $item['description'],
                            'quantity' => 1,
                            'unit_price' => $item['amount'],
                            'subtotal' => $item['amount'],
                            'vat_amount' => 0,
                            'subtotal_with_vat' => $item['amount'],
                            'meta' => ['source' => 'generated'],
                        ]);

                        // Si es gasto, marcarlo como incluido
                        if (str_starts_with($item['id'], 'expense-')) {
                            $expenseId = (int) str_replace('expense-', '', $item['id']);
                            ContractExpense::where('id', $expenseId)->update([
                                'voucher_id' => $voucher->id,
                                'included_in_voucher' => true,
                            ]);
                        }
                    }

                    // Recalcular totales
                    app(\App\Services\VoucherCalculationService::class)->calculateVoucher($voucher);
                    $voucher->save();
                } else {
                    \Log::info("no existe voucher para la moneda y lo crea");
                    // 🆕 Crear voucher nuevo en esta moneda
                    $voucherData = [
                        'voucher_type_short_name' => 'FAC',
                        'booklet_id' => $booklet->id,
                        'contract_id' => $contract->id,
                        'client_id' => $contract->mainTenant?->client_id,
                        'client_name' => $contract->mainTenant?->client?->full_name,
                        'currency' => $currency,
                        'period' => $period->toDateString(),
                        'issue_date' => now()->toDateString(),
                        'due_date' => now()->copy()->addDays(10)->toDateString(),
                        'notes' => 'Cobranza mensual generada automáticamente.',
                        'items' => collect($currencyItems)->map(fn ($item) => [
                            'type' => $this->resolveItemType($item['id']),
                            'description' => $item['description'],
                            'quantity' => 1,
                            'unit_price' => $item['amount'],
                            'subtotal' => $item['amount'],
                            'vat_amount' => 0,
                            'subtotal_with_vat' => $item['amount'],
                            'meta' => ['source' => 'generated'],
                        ])->toArray(),
                    ];

                    $voucher = $voucherService->createFromArray($voucherData);

                    // 🔗 Marcar gastos incluidos en este nuevo voucher
                    foreach ($currencyItems as $item) {
                        if (str_starts_with($item['id'], 'expense-')) {
                            $expenseId = (int) str_replace('expense-', '', $item['id']);
                            ContractExpense::where('id', $expenseId)->update([
                                'voucher_id' => $voucher->id,
                                'included_in_voucher' => true,
                            ]);
                        }
                    }
                }

                $vouchers[] = $voucher->load('items');
            }

            return $vouchers;
        });
    }

    /**
     * Agrupa ítems seleccionados por moneda.
     */
    protected function groupItemsByCurrency(Contract $contract, array $items): array
    {
        $grouped = [];

        foreach ($items as $item) {
            if ($item['id'] === 'expense-rent') {
                $grouped[$contract->currency][] = [
                    'id' => 'rent',
                    'description' => "Renta {$contract->id}",
                    'amount' => $item['amount'],
                ];
            } elseif (str_starts_with($item['id'], 'expense-')) {
                $expenseId = (int) str_replace('expense-', '', $item['id']);
                $expense = ContractExpense::findOrFail($expenseId);
                $grouped[$expense->currency][] = [
                    'id' => "expense-{$expenseId}",
                    'description' => ($expense->description === null ? 'Gasto' : $expense->description),
                    'amount' => $item['amount'],
                ];
            } else {
                throw ValidationException::withMessages([
                    'items' => "Ítem desconocido: {$item['id']}",
                ]);
            }
        }

        return $grouped;
    }

    /**
     * Determina el tipo de ítem del voucher.
     */
    protected function resolveItemType(string $itemId): string
    {
        if ($itemId === 'expense-rent') {
            return 'rent';
        }
        if (str_starts_with($itemId, 'expense-')) {
            return 'expense';
        }
        return 'other';
    }

}
