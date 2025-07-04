<?php

namespace App\Services;

use Illuminate\Support\Collection as SupportCollection;
use App\Models\Collection;
use App\Models\CollectionItem;
use App\Models\Contract;
use App\Enums\CollectionItemType;
use App\Exceptions\CollectionGenerationException;
use Carbon\Carbon;

class CollectionGenerationService
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
            ->with(['clients', 'adjustments', 'collections'])
            ->get()
            ->each(function ($contract) use (&$summary, $period) {
                $summary['total_contracts']++;
                if ($this->hasPendingCollectionsForPreviousPeriods($contract, $period)) {
                    $tenant = $contract->mainTenant();
                    $summary['blocked_due_to_previous_pending']++;
                    $summary['blocked_contracts'][] = [
                        'id' => $contract->id,
                        'name' => $tenant?->name . ' ' . $tenant?->last_name,
                        'reason' => 'pending_previous_periods',
                    ];
                    return;
                }

                if ($this->hasMissingCollections($contract, $period)) {
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

                $existingCurrencies = $contract->collections
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

        $contracts = Contract::activeDuring($period)
            ->with(['collections', 'expenses'])
            ->get();

        $generated = collect();

        foreach ($contracts as $contract) {
            if ($this->hasPendingCollectionsForPreviousPeriods($contract, $period)) {
                \Log::debug("Bloqueado: contrato {$contract->id} tiene cobranzas previas pendientes");
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

            $alreadyGeneratedCurrencies = $contract->collections
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
                    return $item['amount'] ?? 0;
                });

                \Log::debug("Total a cobrar en {$currency}: {$totalAmount}");

                $collection = Collection::create([
                    'client_id' => $tenant->client_id,
                    'contract_id' => $contract->id,
                    'currency' => $currency,
                    'issue_date' => now(),
                    'due_date' => now()->addDays(10),
                    'period' => $period->format('Y-m'),
                    'status' => 'pending',
                    'total_amount' => $totalAmount,
                ]);

                foreach ($groupedItems as $item) {
                    $createdItem = $collection->items()->create($item);

                    if (
                        $createdItem->type === CollectionItemType::SERVICE &&
                        !empty($createdItem->meta['expense_id'])
                    ) {
                        \App\Models\ContractExpense::where('id', $createdItem->meta['expense_id'])
                            ->update(['included_in_collection' => true]);
                    }
                }

                $generated->push($collection);
            }
        }

        if ($generated->isEmpty()) {
            throw new CollectionGenerationException([
                'reason' => 'pending_previous_periods',
                'message' => 'No se pueden generar cobranzas porque hay períodos previos pendientes.',
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

        $validCollections = $contract->collections
            ->where('status', '!=', 'canceled')
            ->groupBy('period');

        while ($startMonth->lt($targetMonth)) {
            $monthKey = $startMonth->format('Y-m');
            $collectionsForMonth = $validCollections->get($monthKey);

            foreach ($possibleCurrencies as $currency) {
                $hasCurrency = $collectionsForMonth?->contains('currency', $currency);
                if (!$hasCurrency) {
                    return $startMonth;
                }
            }

            $startMonth->addMonth();
        }

        return null;
    }

    protected function hasPendingCollectionsForPreviousPeriods(Contract $contract, Carbon $period): bool
    {
        $contract->unsetRelation('collections');
        $contract->loadMissing('expenses');
        $contract->load('collections');

        $startMonth = normalizePeriodOrFail($contract->start_date);
        $targetMonth = normalizePeriodOrFail($period);

        $collectionsByPeriod = $contract->collections
            ->where('status', '!=', 'canceled')
            ->groupBy('period');

        $includedExpenses = $contract->expenses()
            ->where('included_in_collection', true)
            ->get()
            ->groupBy(function ($expense) {
                return normalizePeriodOrFail($expense->period)->format('Y-m');
            });

        while ($startMonth->lt($targetMonth)) {
            $monthKey = $startMonth->format('Y-m');

            $expectedCurrencies = collect([$contract->currency]);
            if ($includedExpenses->has($monthKey)) {
                $expenseCurrencies = $includedExpenses->get($monthKey)->pluck('currency')->unique();
                $expectedCurrencies = $expectedCurrencies->merge($expenseCurrencies)->unique();
            }

            $collectionsForMonth = $collectionsByPeriod->get($monthKey) ?? collect();

            foreach ($expectedCurrencies as $currency) {
                $hasCurrency = $collectionsForMonth->contains('currency', $currency);
                if (! $hasCurrency) {
                    return true;
                }
            }

            $startMonth->addMonth();
        }

        return false;
    }

    protected function hasMissingCollections(Contract $contract, Carbon $period): bool
    {
        $contract->unsetRelation('collections');
        $contract->load('collections');

        $startMonth = normalizePeriodOrFail($contract->start_date);
        $targetMonth = normalizePeriodOrFail($period);

        $collectionsByPeriod = $contract->collections
            ->where('status', '!=', 'canceled')
            ->groupBy('period');

        $includedExpenses = $contract->expenses()
            ->where('included_in_collection', true)
            ->get()
            ->groupBy(function ($expense) {
                return normalizePeriodOrFail($expense->period)->format('Y-m');
            });

        while ($startMonth->lt($targetMonth)) {
            $monthKey = $startMonth->format('Y-m');

            $expectedCurrencies = collect([$contract->currency]);

            if ($includedExpenses->has($monthKey)) {
                $expenseCurrencies = $includedExpenses->get($monthKey)->pluck('currency')->unique();
                $expectedCurrencies = $expectedCurrencies->merge($expenseCurrencies)->unique();
            }

            $collectionsForMonth = $collectionsByPeriod->get($monthKey) ?? collect();

            foreach ($expectedCurrencies as $currency) {
                $hasCurrency = $collectionsForMonth->contains('currency', $currency);
                if (! $hasCurrency) {
                    return true;
                }
            }

            $startMonth->addMonth();
        }

        return false;
    }

    protected function generateItemsForContract(Contract $contract, Carbon $period): \Illuminate\Support\Collection
    {
        $period = normalizePeriodOrFail($period);
        $items = collect();

        $rentData = $contract->calculateRentForPeriod($period);

        if (!empty($rentData['amount']) && $rentData['amount'] > 0) {
            $items->push([
                'type' => CollectionItemType::RENT,
                'description' => 'Alquiler mes ' . $period->translatedFormat('F Y'),
                'quantity' => 1,
                'unit_price' => $rentData['amount'],
                'amount' => $rentData['amount'],
                'currency' => $contract->currency,
                'meta' => $rentData['meta'] ?? [],
            ]);
        }

       if (
            $contract->shouldChargeCommission($period) &&
            $contract->commission_amount > 0
        ) {
            $base = $contract->calculateRentForPeriod($period)['amount'];

            $amount = match ($contract->commission_type->value ?? $contract->commission_type) {
                'fixed' => $contract->commission_amount,
                'percentage' => round($base * ($contract->commission_amount / 100), 2),
                default => 0,
            };

            if ($amount > 0) {
                $items->push([
                    'type' => CollectionItemType::COMMISSION,
                    'description' => 'Comisión inmobiliaria',
                    'quantity' => 1,
                    'unit_price' => $amount,
                    'amount' => $amount,
                    'currency' => $contract->currency,
                    'meta' => [
                        'commission_type' => $contract->commission_type,
                        'commission_value' => $contract->commission_amount,
                        'rent_base' => $base,
                    ],
                ]);
            }
        }


        $expenses = $contract->expenses()
            ->where('paid_by', 'agency')
            ->where('is_paid', true)
            ->where('included_in_collection', false)
            ->where('period', '<=', $period->copy()->startOfMonth()->toDateString())
            ->get();

        foreach ($expenses as $expense) {
            $items->push([
                'type' => CollectionItemType::SERVICE,
                'description' => ucfirst($expense->service_type),
                'quantity' => 1,
                'unit_price' => $expense->amount,
                'amount' => $expense->amount,
                'currency' => $expense->currency,
                'meta' => [
                    'expense_id' => $expense->id,
                    'paid_by' => $expense->paid_by,
                    'expense_period' => $expense->period,
                ],
            ]);
        }

        $penalty = $contract->calculatePenaltyForPeriod($period);
        if ($penalty && $penalty['amount'] > 0) {
            $items->push([
                'type' => CollectionItemType::PENALTY,
                'description' => 'Intereses por mora período ' . $penalty['related_period'],
                'quantity' => 1,
                'unit_price' => $penalty['amount'],
                'amount' => $penalty['amount'],
                'currency' => $contract->currency,
                'meta' => $penalty,
            ]);
        }
        if ($contract->insurance_required && $contract->insurance_amount > 0) {
            $items->push([
                'type' => CollectionItemType::INSURANCE,
                'description' => 'Seguro locativo',
                'quantity' => 1,
                'unit_price' => $contract->insurance_amount,
                'amount' => $contract->insurance_amount,
                'currency' => $contract->currency,
                'meta' => [],
            ]);
        }


        \Log::debug('Items generados en generateItemsForContract', [
            'items' => $items->toArray(),
        ]);
        return $items;
    }

    protected function calculateDueDate(Contract $contract, Carbon $period): Carbon
    {
        $period = normalizePeriodOrFail($period);
        $day = $contract->payment_day ?? 10;
        return $period->copy()->day(min($day, $period->daysInMonth));
    }
}
