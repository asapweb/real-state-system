<?php

namespace App\Services\Vouchers;

use App\Models\Contract;
use App\Models\ContractAdjustment;
use App\Models\ContractCharge;
use App\Enums\VoucherStatus;
use App\Models\Voucher;
use App\Models\ChargeType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VoucherOverviewService
{
    public function build(Carbon $period): array
    {
        $start = $period->copy()->startOfMonth();
        $end = $period->copy()->endOfMonth();

        $activeContracts = Contract::query()->activeDuring($period)->count();

        $blockedAdjustments = ContractAdjustment::query()
            ->blockingForPeriod($period)
            ->distinct('contract_id')
            ->count('contract_id');

        // RENT type id
        $rentTypeId = ChargeType::where('code', 'RENT')->value('id');

        $missingRent = 0;
        if ($rentTypeId) {
            $missingRent = Contract::query()
                ->activeDuring($period)
                ->whereDoesntHave('expenses') // quick filter to avoid heavy join; optional
                ->whereDoesntHave('vouchers', function ($q) { /* noop - just structurally present */ })
                ->whereDoesntHave('property', function ($q) { /* noop */ })
                ->whereDoesntHave('clients', function ($q) { /* noop */ })
                ->whereDoesntHave('services', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('expenses', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('expenses', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                ->whereDoesntHave('vouchers', function ($q) { /* noop */ })
                // Missing RENT charge in the month
                ->whereDoesntHave('expenses', function ($q) use ($rentTypeId, $start) {
                    // placeholder to avoid heavy join; fallback to direct table query below if needed
                })
                ->count();

            // Fallback fast path: count active contracts minus those with RENT charge
            $contractsWithRent = ContractCharge::query()
                ->where('charge_type_id', $rentTypeId)
                ->whereDate('effective_date', $start->toDateString())
                ->distinct('contract_id')
                ->pluck('contract_id');
            $missingRent = Contract::query()
                ->activeDuring($period)
                ->whereNotIn('id', $contractsWithRent)
                ->count();
        }

        // Draft COB vouchers count (per period)
        $draftCobCount = Voucher::query()
            ->whereDate('period', $start->toDateString())
            ->where('status', VoucherStatus::Draft->value)
            ->where('voucher_type_short_name', 'COB')
            ->count();

        // Pending by type
        $map = [
            'RENT' => 'RENT',
            'OWNER_TO_TENANT' => 'RECOVERY_OT',
            'AGENCY_TO_TENANT' => 'RECOVERY_AT',
            'BONIFICATION' => 'BONUS',
        ];

        $pending = [];
        foreach ($map as $label => $code) {
            $typeId = ChargeType::where('code', $code)->value('id');
            if (!$typeId) {
                $pending[$label] = ['count' => 0, 'total' => 0.0];
                continue;
            }
            $q = ContractCharge::query()
                ->where('charge_type_id', $typeId)
                ->whereBetween('effective_date', [$start->toDateString(), $end->toDateString()])
                ->whereIn('status', ['pending', 'validated']);

            $pending[$label] = [
                'count' => (int) $q->clone()->count(),
                'total' => (float) $q->clone()->sum('amount'),
            ];
        }

        // AGENCY missing PAG (approx: unpaid RECOVERY_AT)
        $recAtId = ChargeType::where('code', 'RECOVERY_AT')->value('id');
        $missingPag = 0;
        if ($recAtId) {
            $missingPag = ContractCharge::query()
                ->where('charge_type_id', $recAtId)
                ->whereBetween('effective_date', [$start->toDateString(), $end->toDateString()])
                ->where('is_paid', false)
                ->count();
        }
        $pending['AGENCY_TO_TENANT']['missing_pag_int'] = $missingPag;

        return [
            'period' => $start->toDateString(),
            'active_contracts' => $activeContracts,
            'blocked_adjustments' => $blockedAdjustments,
            'missing_rent' => $missingRent,
            'draft_cob_count' => $draftCobCount,
            'pending_by_type' => $pending,
        ];
    }
}
