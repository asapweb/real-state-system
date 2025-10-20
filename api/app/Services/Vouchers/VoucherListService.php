<?php

namespace App\Services\Vouchers;

use App\Enums\VoucherStatus;
use App\Models\Contract;
use App\Models\ContractAdjustment;
use App\Models\ContractCharge;
use App\Models\Voucher;
use App\Models\ChargeType;
use App\Services\ContractRentCalculator;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

class VoucherListService
{
    public function __construct(private ContractRentCalculator $rentCalculator)
    {
    }

    public function paginate(Carbon $period, array $params): LengthAwarePaginator
    {
        $page = (int) ($params['page'] ?? 1);
        $perPage = (int) ($params['per_page'] ?? 10);
        $sortBy = $params['sort_by'] ?? 'tenant_name';
        $sortDir = strtolower($params['sort_direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $start = $period->copy()->startOfMonth();
        $end = $period->copy()->endOfMonth();

        $query = Contract::query()->activeDuring($period)->with(['property', 'mainTenant.client']);

        // Simple sort mapping
        $sortable = [
            'tenant_name' => fn($q, $dir) => $q->orderBy('id', $dir), // fallback without heavy join
            'property_label' => fn($q, $dir) => $q->orderBy('property_id', $dir),
            'currency' => fn($q, $dir) => $q->orderBy('currency', $dir),
        ];
        if (isset($sortable[$sortBy])) {
            $query = $sortable[$sortBy]($query, $sortDir);
        } else {
            $query->orderBy('id', 'asc');
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $rows = [];

        $rentTypeId = ChargeType::where('code', 'RENT')->value('id');
        $recOtId = ChargeType::where('code', 'RECOVERY_OT')->value('id');
        $recAtId = ChargeType::where('code', 'RECOVERY_AT')->value('id');
        $bonusId = ChargeType::where('code', 'BONUS')->value('id');

        foreach ($paginator->items() as $contract) {
            $issues = [];
            // BLOCKED_ADJUSTMENT
            $hasBlockedAdj = ContractAdjustment::query()
                ->where('contract_id', $contract->id)
                ->blockingForPeriod($period)
                ->exists();
            if ($hasBlockedAdj) $issues[] = 'BLOCKED_ADJUSTMENT';

            // MISSING_RENT
            $hasRent = $rentTypeId ? ContractCharge::query()
                ->where('contract_id', $contract->id)
                ->where('charge_type_id', $rentTypeId)
                ->whereDate('effective_date', $start->toDateString())
                ->exists() : false;
            if (!$hasRent) $issues[] = 'MISSING_RENT';

            // AGENCY_MISSING_PAG (approx via unpaid RECOVERY_AT)
            $agencyMissing = $recAtId ? ContractCharge::query()
                ->where('contract_id', $contract->id)
                ->where('charge_type_id', $recAtId)
                ->whereBetween('effective_date', [$start->toDateString(), $end->toDateString()])
                ->where('is_paid', false)
                ->exists() : false;
            if ($agencyMissing) $issues[] = 'AGENCY_MISSING_PAG';

            // rent amount using calculator
            try {
                $base = $this->rentCalculator->monthlyBaseFor($contract, $period);
                $rentAmount = $this->rentCalculator->applyProrationIfNeeded($contract, $period, $base);
            } catch (\Throwable $e) {
                $rentAmount = (float) ($contract->monthly_amount ?? 0);
            }

            // Pending charges for COB (RENT, RECOVERY_OT, RECOVERY_AT, BONUS)
            $includeTypeIds = array_filter([$rentTypeId, $recOtId, $recAtId, $bonusId]);
            $pendingQuery = ContractCharge::query()
                ->where('contract_id', $contract->id)
                ->whereIn('charge_type_id', $includeTypeIds)
                ->whereBetween('effective_date', [$start->toDateString(), $end->toDateString()])
                ->whereIn('status', ['pending', 'validated']);
            $pendingCount = (int) $pendingQuery->clone()->count();
            $pendingTotal = (float) $pendingQuery->clone()->sum('amount');

            // draft voucher id
            $draftVoucherId = Voucher::query()
                ->where('contract_id', $contract->id)
                ->whereDate('period', $start->toDateString())
                ->where('status', VoucherStatus::Draft->value)
                ->where('voucher_type_short_name', 'COB')
                ->value('id');

            $tenantName = optional($contract->mainTenant?->client)->full_name
                ?? optional($contract->mainTenant?->client)->name
                ?? null;
            $propertyLabel = optional($contract->property)->label
                ?? optional($contract->property)->address
                ?? null;

            $rows[] = [
                'contract_id' => $contract->id,
                'tenant_name' => $tenantName,
                'property_label' => $propertyLabel,
                'currency' => $contract->currency,
                'rent_amount' => (float) $rentAmount,
                'pending_charges_count' => $pendingCount,
                'pending_charges_total' => (float) $pendingTotal,
                'draft_voucher_id' => $draftVoucherId,
                'issues' => $issues,
            ];
        }

        return new Paginator(
            $rows,
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
            ['path' => request()->url(), 'pageName' => 'page']
        );
    }
}
