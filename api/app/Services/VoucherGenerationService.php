<?php

namespace App\Services;

use Illuminate\Support\Collection as SupportCollection;
use App\Models\Voucher;
use App\Models\VoucherItem;
use App\Models\Contract;
use App\Models\Booklet;
use App\Enums\VoucherItemType;
use App\Exceptions\CollectionGenerationException;
use Carbon\Carbon;

class VoucherGenerationService
{
    public function previewForMonth(Carbon $period): array
    {
        $period = normalizePeriodOrFail($period);

        $summary = [
            'period' => $period->format('Y-m'),
            'total_contracts' => 0,
            'already_generated' => 0,
            'pending_generation' => 0,
            'blocked_due_to_missing_months' => 0,
            'blocked_due_to_previous_pending' => 0,
            'blocked_contracts' => [],
        ];

        Contract::activeDuring($period)
            ->with(['clients', 'adjustments', 'vouchers'])
            ->get()
            ->each(function ($contract) use (&$summary, $period) {
                $summary['total_contracts']++;
                if ($this->hasPendingVouchersForPreviousPeriods($contract, $period)) {
                    $tenant = $contract->mainTenant();
                    $summary['blocked_due_to_previous_pending']++;
                    $summary['blocked_contracts'][] = [
                        'id' => $contract->id,
                        'name' => $tenant?->name . ' ' . $tenant?->last_name,
                        'reason' => 'pending_previous_periods',
                    ];
                    return;
                }

                if ($this->hasMissingVouchers($contract, $period)) {
                    $tenant = $contract->mainTenant();
                    $summary['blocked_due_to_missing_months']++;
                    $summary['blocked_contracts'][] = [
                        'id' => $contract->id,
                        'name' => $tenant?->name . ' ' . $tenant?->last_name,
                        'reason' => 'missing_months',
                        'missing_period' => $this->firstMissingMonth($contract, $period)?->format('Y-m'),
                    ];
                    return;
                }

                $existingCurrencies = $contract->vouchers()
                    ->whereHas('booklet.voucherType', function ($query) {
                        $query->where('code', 'COB');
                    })
                    ->where('period', $period->format('Y-m'))
                    ->where('status', '!=', 'canceled')
                    ->pluck('currency')
                    ->unique();

                $items = $this->generateItemsForContract($contract, $period);
                $allCurrencies = collect($items)->pluck('currency')->unique();

                $summary['already_generated'] += $existingCurrencies->count();
                $summary['pending_generation'] += $allCurrencies->diff($existingCurrencies)->count();
            });

        $summary['status'] = match (true) {
            $summary['total_contracts'] === 0 => 'empty',
            $summary['pending_generation'] === 0
                && $summary['blocked_due_to_missing_months'] === 0
                && $summary['blocked_due_to_previous_pending'] === 0 => 'complete',
            $summary['pending_generation'] > 0 => 'partial',
            default => 'blocked',
        };

        return $summary;
    }

    public function generateForMonth(Carbon $period): SupportCollection
    {
        $period = normalizePeriodOrFail($period);

        // Obtener un booklet compatible con COB
        $booklet = Booklet::whereHas('voucherType', function ($query) {
            $query->where('code', 'COB');
        })->first();

        if (!$booklet) {
            throw new \Exception('No se encontró un booklet compatible con tipo COB');
        }

        $contracts = Contract::activeDuring($period)
            ->with(['vouchers', 'expenses'])
            ->get();

        $generated = collect();

        foreach ($contracts as $contract) {
            if ($this->hasPendingVouchersForPreviousPeriods($contract, $period)) {
                \Log::debug("Bloqueado: contrato {$contract->id} tiene vouchers previos pendientes");
                continue;
            }

            $tenant = $contract->mainTenant();
            \Log::debug("Procesando contrato {$contract->id} para cliente {$tenant?->name}");

            $contract->unsetRelation('expenses');
            $contract->load('expenses');

            $items = $this->generateItemsForContract($contract, $period);
            \Log::debug("Ítems generados", ['items' => $items]);

            $itemsGrouped = collect($items)->groupBy('currency');
            \Log::debug("Items agrupados por moneda", ['monedas' => $itemsGrouped->keys()]);

            $alreadyGeneratedCurrencies = $contract->vouchers()
                ->whereHas('booklet.voucherType', function ($query) {
                    $query->where('code', 'COB');
                })
                ->where('period', $period->format('Y-m'))
                ->where('status', '!=', 'canceled')
                ->pluck('currency')
                ->unique();

            foreach ($itemsGrouped as $currency => $groupedItems) {
                \Log::debug("Procesando moneda {$currency}");

                if ($alreadyGeneratedCurrencies->contains($currency)) {
                    \Log::debug("Saltando moneda ya generada: {$currency}");
                    continue;
                }

                \Log::debug("Verificando ítems antes de sumar", [
                    'currency' => $currency,
                    'groupedItems' => $groupedItems->toArray(),
                ]);
                $totalAmount = collect($groupedItems)->sum(function ($item) {
                    \Log::debug('Item en suma', ['item' => $item]);
                    return $item['subtotal'] ?? 0;
                });

                \Log::debug("Total a cobrar en {$currency}: {$totalAmount}");

                $client = $tenant->client()->with('documentType', 'taxCondition')->first();

                $voucher = Voucher::create([
                    'booklet_id' => $booklet->id,
                    'number' => $this->generateVoucherNumber($booklet),
                    'client_id' => $tenant->client_id,
                    'client_name' => $client->name,
                    'client_address' => $client->address,
                    'client_document_type_name' => $client->documentType?->name,
                    'client_document_number' => $client->document_number,
                    'client_tax_condition_name' => $client->taxCondition?->name,
                    'client_tax_id_number' => $client->tax_id_number,
                    'contract_id' => $contract->id,
                    'currency' => $currency,
                    'issue_date' => now(),
                    'due_date' => $this->calculateDueDate($contract, $period),
                    'period' => $period->format('Y-m'),
                    'status' => 'draft',
                    'total' => $totalAmount,
                ]);

                foreach ($groupedItems as $item) {
                    $createdItem = $voucher->items()->create([
                        'type' => $item['type'],
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'subtotal' => $item['subtotal'],
                        'meta' => $item['meta'] ?? [],
                    ]);

                    if (
                        $createdItem->type === VoucherItemType::SERVICE &&
                        !empty($createdItem->meta['expense_id'])
                    ) {
                        \App\Models\ContractExpense::where('id', $createdItem->meta['expense_id'])
                            ->update(['included_in_collection' => true]);
                    }
                }

                $generated->push($voucher);
            }
        }

        if ($generated->isEmpty()) {
            throw new CollectionGenerationException([
                'reason' => 'pending_previous_periods',
                'message' => 'No se pueden generar vouchers porque hay períodos previos pendientes.',
            ]);
        }

        return $generated;
    }

    protected function firstMissingMonth(Contract $contract, Carbon $period): ?Carbon
    {
        $startMonth = normalizePeriodOrFail($contract->start_date);
        $targetMonth = normalizePeriodOrFail($period);

        $possibleCurrencies = $contract->expenses()
            ->where('paid_by', 'agency')
            ->pluck('currency')
            ->merge([$contract->currency])
            ->unique();

        $validVouchers = $contract->vouchers()
            ->whereHas('booklet.voucherType', function ($query) {
                $query->where('code', 'COB');
            })
            ->where('status', '!=', 'canceled')
            ->get()
            ->groupBy('period');

        while ($startMonth->lt($targetMonth)) {
            $monthKey = $startMonth->format('Y-m');
            $vouchersForMonth = $validVouchers->get($monthKey);

            foreach ($possibleCurrencies as $currency) {
                $hasCurrency = $vouchersForMonth?->contains('currency', $currency);
                if (!$hasCurrency) {
                    return $startMonth;
                }
            }

            $startMonth->addMonth();
        }

        return null;
    }

    protected function hasPendingVouchersForPreviousPeriods(Contract $contract, Carbon $period): bool
    {
        $contract->unsetRelation('vouchers');
        $contract->load('vouchers');

        $startMonth = normalizePeriodOrFail($contract->start_date);
        $targetMonth = normalizePeriodOrFail($period);

        $vouchersByPeriod = $contract->vouchers()
            ->whereHas('booklet.voucherType', function ($query) {
                $query->where('code', 'COB');
            })
            ->where('status', '!=', 'canceled')
            ->get()
            ->groupBy('period');

        $possibleCurrencies = $contract->expenses()
            ->where('paid_by', 'agency')
            ->pluck('currency')
            ->merge([$contract->currency])
            ->unique();

        while ($startMonth->lt($targetMonth)) {
            $monthKey = $startMonth->format('Y-m');
            $vouchersForMonth = $vouchersByPeriod->get($monthKey) ?? collect();

            foreach ($possibleCurrencies as $currency) {
                $hasCurrency = $vouchersForMonth->contains('currency', $currency);
                if (!$hasCurrency) {
                    return true;
                }
            }

            $startMonth->addMonth();
        }

        return false;
    }

    protected function hasMissingVouchers(Contract $contract, Carbon $period): bool
    {
        return $this->firstMissingMonth($contract, $period) !== null;
    }

    protected function generateItemsForContract(Contract $contract, Carbon $period): \Illuminate\Support\Collection
    {
        $items = collect();

        // Alquiler
        $rentAmount = $contract->monthly_amount;
        if ($rentAmount > 0) {
            $items->push([
                'type' => VoucherItemType::RENT,
                'description' => 'Alquiler',
                'quantity' => 1,
                'unit_price' => $rentAmount,
                'subtotal' => $rentAmount,
                'currency' => $contract->currency,
                'meta' => [],
            ]);
        }

        // Comisión
        $commissionAmount = $contract->commission_amount;
        if ($commissionAmount > 0) {
            $items->push([
                'type' => VoucherItemType::COMMISSION,
                'description' => 'Comisión',
                'quantity' => 1,
                'unit_price' => $commissionAmount,
                'subtotal' => $commissionAmount,
                'currency' => $contract->currency,
                'meta' => [],
            ]);
        }

        // Seguro
        $insuranceAmount = $contract->insurance_amount;
        if ($insuranceAmount > 0) {
            $items->push([
                'type' => VoucherItemType::INSURANCE,
                'description' => 'Seguro',
                'quantity' => 1,
                'unit_price' => $insuranceAmount,
                'subtotal' => $insuranceAmount,
                'currency' => $contract->currency,
                'meta' => [],
            ]);
        }

        // Servicios
        $contract->expenses()
            ->where('paid_by', 'agency')
            ->where('included_in_collection', false)
            ->get()
            ->each(function ($expense) use ($items) {
                $items->push([
                    'type' => VoucherItemType::SERVICE,
                    'description' => $expense->description,
                    'quantity' => 1,
                    'unit_price' => $expense->amount,
                    'subtotal' => $expense->amount,
                    'currency' => $expense->currency,
                    'meta' => [
                        'expense_id' => $expense->id,
                    ],
                ]);
            });

        // Penalizaciones
        $penaltyAmount = $contract->penalty_amount;
        if ($penaltyAmount > 0) {
            $items->push([
                'type' => VoucherItemType::PENALTY,
                'description' => 'Penalización',
                'quantity' => 1,
                'unit_price' => $penaltyAmount,
                'subtotal' => $penaltyAmount,
                'currency' => $contract->currency,
                'meta' => [],
            ]);
        }

        return $items;
    }

    protected function calculateDueDate(Contract $contract, Carbon $period): Carbon
    {
        return now()->addDays(10);
    }

    protected function generateVoucherNumber(Booklet $booklet): string
    {
        return $booklet->generateNextNumber();
    }
}
