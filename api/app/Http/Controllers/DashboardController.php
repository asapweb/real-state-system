<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractCharge;
use App\Models\ContractAdjustment;
use App\Models\Voucher;
use App\Models\VoucherType;
use App\Models\VoucherApplication;
use App\Models\ChargeType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * GET /api/dashboard/summary?period=YYYY-MM&currency=ARS|USD|ALL
     */
    public function summary(Request $request): JsonResponse
    {
        $periodStr = (string) $request->query('period');
        if (!$periodStr) {
            return response()->json(['message' => 'Parámetro period requerido (YYYY-MM)'], 422);
        }

        try {
            $period = Carbon::createFromFormat('Y-m', $periodStr)->startOfMonth();
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Formato inválido. Use YYYY-MM'], 422);
        }

        $from = $period->copy()->startOfMonth()->toDateString();
        $to   = $period->copy()->addMonth()->startOfMonth()->toDateString();
        $currency = $request->query('currency', 'ALL');

        // IDs de tipos
        $rentTypeId    = ChargeType::where('code', 'RENT')->value('id');
        $lqiTypeId     = VoucherType::where('short_name', 'LQI')->value('id');
        $cobTypeId     = VoucherType::where('short_name', 'COB')->value('id');
        $rcbTypeId     = VoucherType::where('short_name', 'RCB')->value('id');
        $liqOwnerTypeId= VoucherType::where('short_name', 'LIQ')->value('id');
        $rpgTypeId     = VoucherType::where('short_name', 'RPG')->value('id');

        // Contratos activos en el período
        $contractsActive = Contract::activeDuring($period)->count();

        // Ajustes del período
        $adjBase = ContractAdjustment::query()
            ->where('effective_date', '>=', $from)
            ->where('effective_date', '<',  $to);

        $adjScheduled = (clone $adjBase)->count();
        $adjApplied   = (clone $adjBase)->whereNotNull('applied_at')->count();
        $adjPending   = max(0, $adjScheduled - $adjApplied);

        // RENT generadas
        $rentCharges = ContractCharge::query()
            ->when($rentTypeId, fn($q) => $q->where('charge_type_id', $rentTypeId))
            ->where('effective_date', '>=', $from)
            ->where('effective_date', '<',  $to)
            ->when($currency !== 'ALL', fn($q) => $q->where('currency', $currency));

        $generated = (clone $rentCharges)->count();
        $duplicates = (clone $rentCharges)
            ->select('contract_id')
            ->groupBy('contract_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()->count();
        $expected  = $contractsActive; // ajustar por prorrateo si aplica
        $missing   = max(0, $expected - $generated);

        // LQI pendientes de generar: contratos con cargos del período sin voucher asociado
        $toGenerateLqi = Contract::activeDuring($period)
            ->whereHas('charges', function ($q) use ($from, $to, $currency) {
                $q->where('effective_date', '>=', $from)
                  ->where('effective_date', '<',  $to)
                  ->whereNull('tenant_liquidation_voucher_id')
                  ->where('is_canceled', false)
                  ->when($currency !== 'ALL', fn($qq) => $qq->where('currency', $currency));
                // TODO: si tu modelo define liquidation_mode/billing_mode, filtrar sólo cargos aplicables al inquilino
            })
            ->count();

        // LQI por estado en el período (según issue_date dentro del mes)
        $lqiDraft  = Voucher::query()
            ->whereHas('booklet.voucherType', fn($q) => $q->where('short_name', 'LQI'))
            ->whereNull('issue_date')
            ->where('period', '>=', $from) // si guardás período, si no, eliminar
            ->where('period', '<',  $to)
            ->count();

        $lqiIssued = Voucher::query()
            ->whereHas('booklet.voucherType', fn($q) => $q->where('short_name', 'LQI'))
            ->whereNotNull('issue_date')
            ->where('issue_date', '>=', $from)
            ->where('issue_date', '<',  $to)
            ->count();

        // Con saldo (aplicaciones RCB vs total)
        $lqiWithBalance = Voucher::query()
            ->whereHas('booklet.voucherType', fn($q) => $q->where('short_name', 'LQI'))
            ->whereNotNull('issue_date')
            ->where('issue_date', '>=', $from)
            ->where('issue_date', '<',  $to)
            ->withSum('applications as applied_amount', 'amount')
            ->get()
            ->filter(function ($v) {
                $applied = (float) ($v->applied_amount ?? 0);
                return round(((float) $v->total) - $applied, 2) > 0;
            })
            ->count();

        // Cobranzas (COB/RCB)
        $invoiced = Voucher::query()
            ->when($cobTypeId, fn($q) => $q->where('voucher_type_id', $cobTypeId))
            ->whereNotNull('issue_date')
            ->when($currency !== 'ALL', fn($q) => $q->where('currency', $currency))
            ->where('issue_date', '>=', $from)
            ->where('issue_date', '<',  $to)
            ->sum('total');

        // $collected = VoucherApplication::query()
        //     ->whereHas('sourceVoucher.booklet.voucherType', fn($q) => $q->where('short_name', 'RCB'))
        //     ->when($currency !== 'ALL', fn($q) => $q->whereHas('targetVoucher', fn($qq) => $qq->where('currency', request('currency'))))
        //     ->where('created_at', '>=', $from)
        //     ->where('created_at', '<',  $to)
        //     ->sum('amount');

        $rate = 0.0; // $invoiced > 0 ? round($collected / $invoiced, 4) : 0.0;

        // LIQ Propietarios y RPG ejecutados
        $issuedOwnerLiq = Voucher::query()
            ->when($liqOwnerTypeId, fn($q) => $q->where('voucher_type_id', $liqOwnerTypeId))
            ->whereNotNull('issue_date')
            ->when($currency !== 'ALL', fn($q) => $q->where('currency', $currency))
            ->where('issue_date', '>=', $from)
            ->where('issue_date', '<',  $to)
            ->sum('total');

        $rpgExecuted = Voucher::query()
            ->when($rpgTypeId, fn($q) => $q->where('voucher_type_id', $rpgTypeId))
            ->whereNotNull('issue_date')
            ->when($currency !== 'ALL', fn($q) => $q->where('currency', $currency))
            ->where('issue_date', '>=', $from)
            ->where('issue_date', '<',  $to)
            ->sum('total');

        return response()->json([
            'period'   => $period->format('Y-m'),
            'currency' => $currency,
            'totals'   => [
                'contracts_active' => $contractsActive,
                'adjustments' => [
                    'scheduled' => $adjScheduled,
                    'applied'   => $adjApplied,
                    'pending'   => $adjPending,
                ],
                'rent' => [
                    'expected'  => $expected,
                    'generated' => $generated,
                    'missing'   => $missing,
                    'duplicates'=> $duplicates,
                ],
                'lqi' => [
                    'to_generate' => $toGenerateLqi,
                    'draft'       => $lqiDraft,
                    'issued'      => $lqiIssued,
                    'with_balance'=> $lqiWithBalance,
                ],
                'collections' => [
                    'invoiced' => (float) $invoiced,
                    // 'collected'=> (float) $collected,
                    'rate'     => $rate,
                ],
                'liq_owners' => [
                    'issued'      => (float) $issuedOwnerLiq,
                ],
                'rpg' => [
                    'executed'    => (float) $rpgExecuted,
                ],
            ],
        ]);
    }

    /**
     * GET /api/dashboard/rents?period=YYYY-MM&currency=ALL|ARS|USD&per_page=25&sort_by=...&sort_direction=asc|desc
     * Devuelve RENT del período con info básica para tabla server-side.
     */
    public function rents(Request $request): JsonResponse
    {
        $periodStr = (string) $request->query('period');
        if (!$periodStr) {
            return response()->json(['message' => 'Parámetro period requerido (YYYY-MM)'], 422);
        }
        try {
            $period = Carbon::createFromFormat('Y-m', $periodStr)->startOfMonth();
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Formato inválido. Use YYYY-MM'], 422);
        }
        $from = $period->copy()->startOfMonth()->toDateString();
        $to   = $period->copy()->addMonth()->startOfMonth()->toDateString();
        $currency = $request->query('currency', 'ALL');

        $perPage = (int) $request->integer('per_page', 25);
        $sortBy  = $request->query('sort_by', 'effective_date');
        $dir     = $request->query('sort_direction', 'asc') === 'desc' ? 'desc' : 'asc';

        $rentTypeId = ChargeType::where('short_name', 'RENT')->value('id');

        $query = ContractCharge::query()
            ->with(['contract:id,code,tenant_id,property_id', 'contract.tenant:id,first_name,last_name', 'contract.property:id,address'])
            ->when($rentTypeId, fn($q) => $q->where('charge_type_id', $rentTypeId))
            ->where('effective_date', '>=', $from)
            ->where('effective_date', '<',  $to)
            ->when($currency !== 'ALL', fn($q) => $q->where('currency', $currency));

        // Campos permitidos para ordenar
        $allowedSort = ['effective_date', 'amount', 'currency', 'contract_id'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'effective_date';
        }
        $query->orderBy($sortBy, $dir);

        $paginator = $query->paginate($perPage)->appends($request->query());

        return response()->json($paginator);
    }

    /**
     * GET /api/dashboard/lqi?period=YYYY-MM&currency=...&per_page=25
     * Lista LQI del período con aplicado y saldo.
     */
    public function lqi(Request $request): JsonResponse
    {
        $periodStr = (string) $request->query('period');
        if (!$periodStr) {
            return response()->json(['message' => 'Parámetro period requerido (YYYY-MM)'], 422);
        }
        try {
            $period = Carbon::createFromFormat('Y-m', $periodStr)->startOfMonth();
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Formato inválido. Use YYYY-MM'], 422);
        }
        $from = $period->copy()->startOfMonth()->toDateString();
        $to   = $period->copy()->addMonth()->startOfMonth()->toDateString();
        $currency = $request->query('currency', 'ALL');

        $perPage = (int) $request->integer('per_page', 25);

        $query = Voucher::query()
            ->with(['contract:id,code', 'booklet.voucherType'])
            ->whereHas('booklet.voucherType', fn($q) => $q->where('short_name', 'LQI'))
            ->whereNotNull('issue_date')
            ->where('issue_date', '>=', $from)
            ->where('issue_date', '<',  $to)
            ->when($currency !== 'ALL', fn($q) => $q->where('currency', $currency))
            ->withSum('applications as applied_amount', 'amount')
            ->orderBy('issue_date', 'desc');

        $paginator = $query->paginate($perPage)->through(function ($v) {
            $applied = (float) ($v->applied_amount ?? 0);
            $total   = (float) $v->total;
            $balance = round($total - $applied, 2);
            return [
                'id'            => $v->id,
                'number'        => $v->full_number ?? $v->number,
                'contract_code' => $v->contract->code ?? null,
                'issue_date'     => $v->issue_date,
                'status'        => $v->status,
                'items_count'   => $v->items()->count(),
                'total'         => $total,
                'applied'       => $applied,
                'balance'       => $balance,
            ];
        })->appends($request->query());

        return response()->json($paginator);
    }
}
