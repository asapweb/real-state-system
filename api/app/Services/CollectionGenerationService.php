<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\CollectionItem;
use App\Models\Contract;
use App\Enums\CollectionItemType;
use App\Enums\ContractClientRole;
use App\Exceptions\CollectionGenerationException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CollectionGenerationService
{
    public function previewForMonth(Carbon $period): array
    {
        $summary = [
            'period' => $period->format('Y-m'),
            'total_contracts' => 0,
            'already_generated' => 0,
            'pending_generation' => 0,
            'blocked_due_to_missing_months' => 0,
            'blocked_contracts' => [],
        ];

        Contract::activeDuring($period)
            ->with(['clients', 'collections'])
            ->get()
            ->each(function ($contract) use (&$summary, $period) {
                $summary['total_contracts']++;

                if ($contract->collections->firstWhere('period', $period->format('Y-m'))) {
                    $summary['already_generated']++;
                    return;
                }

                if ($this->hasMissingCollections($contract, $period)) {
                    $summary['blocked_due_to_missing_months']++;

                    $tenant = $contract->clients->firstWhere('role', ContractClientRole::TENANT);
                    $summary['blocked_contracts'][] = [
                        'id' => $contract->id,
                        'name' => $tenant?->name . ' ' . $tenant?->last_name,
                        'missing_period' => $this->firstMissingMonth($contract, $period)?->format('Y-m'),
                    ];
                    return;
                }

                $summary['pending_generation']++;
            });

        $summary['status'] = match (true) {
            $summary['total_contracts'] === 0 => 'empty',
            $summary['pending_generation'] === 0 && $summary['blocked_due_to_missing_months'] === 0 => 'complete',
            $summary['pending_generation'] > 0 => 'partial',
            default => 'blocked',
        };

        return $summary;
    }

    public function generateForMonth(Carbon $period): int
    {
        $problematicContracts = [];
        $generated = 0;

        Contract::activeDuring($period)
            ->with([
                'clients',
                'adjustments' => fn($q) => $q
                    ->whereMonth('effective_date', $period->month)
                    ->whereYear('effective_date', $period->year),
                'collections',
            ])
            ->get()
            ->each(function ($contract) use ($period, &$problematicContracts, &$generated) {
                if ($contract->collections->firstWhere('period', $period->format('Y-m'))) {
                    return;
                }

                if ($this->hasMissingCollections($contract, $period)) {
                    $tenant = $contract->clients->firstWhere('role', ContractClientRole::TENANT);
                    $problematicContracts[] = [
                        'id' => $contract->id,
                        'name' => $tenant?->name . ' ' . $tenant?->last_name,
                        'period_missing' => $this->firstMissingMonth($contract, $period)?->format('Y-m'),
                    ];
                    return;
                }

                DB::transaction(function () use ($contract, $period, &$generated) {
                    $tenant = $contract->clients->firstWhere('role', ContractClientRole::TENANT);
                    if (!$tenant) {
                        throw new \RuntimeException("No tenant found for contract ID {$contract->id}");
                    }

                    $collection = Collection::create([
                        'client_id' => $tenant->client_id,
                        'contract_id' => $contract->id,
                        'currency' => $contract->currency,
                        'issue_date' => now(),
                        'due_date' => $this->calculateDueDate($contract, $period),
                        'period' => $period->format('Y-m'),
                        'total_amount' => 0,
                    ]);

                    $items = $this->generateItemsForContract($contract, $period);
                    $total = 0;

                    foreach ($items as $item) {
                        $item['collection_id'] = $collection->id;
                        CollectionItem::create($item);
                        $total += $item['amount'];
                    }

                    $collection->update(['total_amount' => $total]);
                    $generated++;
                });
            });

        if (!empty($problematicContracts)) {
            throw new CollectionGenerationException($problematicContracts);
        }

        return $generated;
    }


    protected function firstMissingMonth(Contract $contract, Carbon $period): ?Carbon
    {
        $startMonth = Carbon::parse($contract->start_date)->copy()->startOfMonth();
        $targetMonth = $period->copy()->startOfMonth();

        $existing = $contract->collections
            ->pluck('period')
            ->map(fn($p) => Carbon::createFromFormat('Y-m', $p)->startOfMonth());

        while ($startMonth->lt($targetMonth)) {
            if (!$existing->contains(fn($p) => $p->equalTo($startMonth))) {
                return $startMonth;
            }
            $startMonth->addMonth();
        }

        return null;
    }

    protected function hasMissingCollections(Contract $contract, Carbon $period): bool
    {
        $startMonth = Carbon::parse($contract->start_date)->copy()->startOfMonth();
        $targetMonth = $period->copy()->startOfMonth();

        $existingPeriods = $contract->collections
            ->pluck('period')
            ->map(fn($p) => Carbon::createFromFormat('Y-m', $p)->startOfMonth());

        while ($startMonth->lt($targetMonth)) {
            if (!$existingPeriods->contains(fn($p) => $p->equalTo($startMonth))) {
                return true;
            }
            $startMonth->addMonth();
        }

        return false;
    }

    protected function generateItemsForContract(Contract $contract, Carbon $period): array
    {
        $items = [];

        $rentInfo = $contract->calculateRentForPeriod($period);
        $appliedAdjustment = $rentInfo['meta']['adjustment'] ?? null;

        // ✅ Marcar el ajuste como aplicado si corresponde
        if ($appliedAdjustment instanceof \App\Models\ContractAdjustment) {
            $appliedAdjustment->markAsApplied();
        }

        if ($rentInfo['amount'] > 0) {
            // 👉 Preparar metadata sin exponer toda la instancia
            $meta = $rentInfo['meta'] ?? [];
            if ($appliedAdjustment) {
                $meta = array_merge($meta, [
                    'adjustment_id' => $appliedAdjustment->id,
                    'adjustment_type' => $appliedAdjustment->type,
                    'adjustment_value' => $appliedAdjustment->value,
                ]);
            }

            $items[] = [
                'type' => CollectionItemType::Rent,
                'description' => 'Alquiler mes ' . $period->translatedFormat('F Y'),
                'quantity' => 1,
                'unit_price' => $rentInfo['amount'],
                'amount' => $rentInfo['amount'],
                'currency' => $contract->currency,
                'meta' => $meta,
            ];
        }

        if ($contract->shouldChargeCommission($period)) {
            $items[] = [
                'type' => CollectionItemType::Commission,
                'description' => 'Comisión inmobiliaria',
                'quantity' => 1,
                'unit_price' => $contract->commission_amount,
                'amount' => $contract->commission_amount,
                'currency' => $contract->currency,
                'meta' => [],
            ];
        }

        if ($contract->insurance_required) {
            $items[] = [
                'type' => CollectionItemType::Insurance,
                'description' => 'Seguro de hogar',
                'quantity' => 1,
                'unit_price' => $contract->insurance_amount,
                'amount' => $contract->insurance_amount,
                'currency' => $contract->currency,
                'meta' => [],
            ];
        }

        if ($penalty = $contract->calculatePenaltyForPeriod($period)) {
            $items[] = [
                'type' => CollectionItemType::Penalty,
                'description' => 'Punitorio por atraso – ' . $penalty['related_period'],
                'quantity' => 1,
                'unit_price' => $penalty['amount'],
                'amount' => $penalty['amount'],
                'currency' => $contract->currency,
                'meta' => $penalty,
            ];
        }

        return $items;
    }


    protected function calculateDueDate(Contract $contract, Carbon $period): Carbon
    {
        $day = $contract->payment_day ?? 10;
        return $period->copy()->day(min($day, $period->daysInMonth));
    }
}
