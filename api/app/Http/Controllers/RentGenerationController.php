<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateRentRequest;
use App\Models\Contract;
use App\Models\ContractAdjustment;
use App\Models\ContractCharge;
use App\Models\ChargeType;
use App\Services\RentGenerationService;
use App\Services\ContractRentCalculator;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RentGenerationController extends Controller
{
    public function __construct(private RentGenerationService $service) {}

    public function index(Request $request)
    {
        // Periodo
        $periodStr = $request->input('period'); // 'YYYY-MM'
        if (!$periodStr) {
            return response()->json(['message' => 'Parámetro period requerido (YYYY-MM)'], 422);
        }

        $period = Carbon::createFromFormat('Y-m', $periodStr);
        if (!$period) {
            return response()->json(['message' => 'Formato de período inválido (YYYY-MM)'], 422);
        }

        $from = $period->copy()->startOfMonth()->toDateString();
        $to   = $period->copy()->startOfMonth()->addMonth()->toDateString();

        // Tipo de cargo "RENT"
        $rentTypeId = ChargeType::where('code', 'RENT')->value('id');
        if (!$rentTypeId) {
            return response()->json(['message' => "No se encontró ChargeType con code=RENT"], 422);
        }

        // Parámetros de paginación/orden
        $perPage       = max(1, (int) $request->integer('per_page', 25));
        $sortBy        = (string) $request->query('sort_by', 'id');
        $sortDirection = strtolower((string) $request->query('sort_direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        // Búsquedas y filtro de estado
        $search = $request->input('search', []); // ej: ['id' => 123, 'tenant_id' => 123, 'property_id' => 456]
        $filter = strtolower((string) $request->query('filter', '')); // pending_rent | rent_pending | open

        // ---------- Subqueries ----------
        // Ajuste pendiente (incluye arrastrados): cualquier ajuste con effective_date < $to y applied_at IS NULL
        $pendingAdjExistsSub = ContractAdjustment::query()
            ->selectRaw('1')
            ->whereColumn('contract_id', 'contracts.id')
            ->whereNull('applied_at')
            ->where('effective_date', '<', $to)
            ->limit(1);

        // Último ajuste APLICADO antes de fin del período (para period_value)
        $lastAppliedAdjAmountSub = ContractAdjustment::query()
            ->select('applied_amount')
            ->whereColumn('contract_id', 'contracts.id')
            ->whereNotNull('applied_at')
            ->where('effective_date', '<', $to)
            ->orderBy('effective_date', 'desc')
            ->limit(1);

        // Último ajuste PLANIFICADO dentro del período (detalle UI)
        $lastPlannedAdjJsonSub = ContractAdjustment::query()
            ->selectRaw("
                JSON_OBJECT(
                    'id', id,
                    'type', `type`,
                    'effective_date', DATE_FORMAT(effective_date, '%Y-%m-%d'),
                    'applied_amount', applied_amount
                )
            ")
            ->whereColumn('contract_id', 'contracts.id')
            // ->where('effective_date', '>=', $from)
            ->where('effective_date', '<', $to)
            ->orderBy('effective_date', 'desc')
            ->limit(1);

        // Base de renta del período
        $rentChargeBase = ContractCharge::query()
            ->whereColumn('contract_id', 'contracts.id')
            ->where('charge_type_id', $rentTypeId)
            ->where('effective_date', '>=', $from)
            ->where('effective_date', '<',  $to);

        // JSON renta del período
        $rentInfoJsonSub = (clone $rentChargeBase)
            ->selectRaw("
                JSON_OBJECT(
                    'id', id,
                    'status', `status`,
                    'amount', amount,
                    'effective_date', DATE_FORMAT(effective_date, '%Y-%m-%d')
                )
            ")
            ->orderByDesc('id')
            ->limit(1);

        // Estado de la renta (para CASE de status)
        $rentStatusSub = (clone $rentChargeBase)
            ->select('status')
            ->orderByDesc('id')
            ->limit(1);

        // EXISTS renta (cualquiera)
        $rentExistsSql = (clone $rentChargeBase)->selectRaw('1')->limit(1);

        // EXISTS renta en estado 'pending'
        $rentPendingExistsSql = (clone $rentChargeBase)->where('status', 'pending')->selectRaw('1')->limit(1);

        // ---------- Query principal ----------
        $q = Contract::query()
            ->activeDuring($period)
            ->select([
                'contracts.id',
                'contracts.property_id',
                'contracts.currency',
                'contracts.monthly_amount',
                'contracts.start_date',
                'contracts.end_date',
            ])
            ->with(['property', 'mainTenant.client'])

            // Último ajuste planificado dentro del período (JSON)
            ->addSelect(['last_planned_adjustment' => $lastPlannedAdjJsonSub])

            // Renta del período (JSON)
            ->addSelect(['rent_info' => $rentInfoJsonSub])

            // period_value: 0 si hay ajuste pendiente; sino último aplicado o monthly_amount
            ->selectRaw("
                CASE
                    WHEN EXISTS (".$pendingAdjExistsSub->toSql().")
                        THEN 0
                    ELSE COALESCE( (".$lastAppliedAdjAmountSub->toSql()."), contracts.monthly_amount )
                END AS period_value
            ", array_merge(
                $pendingAdjExistsSub->getBindings(),
                $lastAppliedAdjAmountSub->getBindings()
            ))

            // status final
            ->selectRaw("
                CASE
                    WHEN EXISTS (".$pendingAdjExistsSub->toSql().")
                        THEN 'pending_adjustment'
                    WHEN NOT EXISTS (".$rentExistsSql->toSql().")
                        THEN 'pending'
                    ELSE (".$rentStatusSub->toSql().")
                END AS `rent_status`
            ", array_merge(
                $pendingAdjExistsSub->getBindings(),
                $rentExistsSql->getBindings(),
                $rentStatusSub->getBindings()
            ));

        // ---------- Filtros de búsqueda ----------
        if ($request->filled('contract_id')) {
            $q->where('contracts.id', (int) $request->input('contract_id'));
        }

        // ---------- Filtro de estado ----------
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'pending_rent') {
                // Sin ajuste pendiente y sin renta
                $q->whereRaw('NOT EXISTS ('.$pendingAdjExistsSub->toSql().')', $pendingAdjExistsSub->getBindings())
                ->whereRaw('NOT EXISTS ('.$rentExistsSql->toSql().')',       $rentExistsSql->getBindings());
            } elseif ($status === 'rent_pending') {
                // Con renta en estado pending
                $q->whereRaw('EXISTS ('.$rentPendingExistsSql->toSql().')', $rentPendingExistsSql->getBindings());
            } elseif ($status === 'open') {
                // Pendiente de generar renta  OR  renta generada en estado pending
                $q->where(function ($qq) use ($pendingAdjExistsSub, $rentExistsSql, $rentPendingExistsSql) {
                    $qq->whereRaw('NOT EXISTS ('.$pendingAdjExistsSub->toSql().')', $pendingAdjExistsSub->getBindings())
                    ->whereRaw('NOT EXISTS ('.$rentExistsSql->toSql().')',       $rentExistsSql->getBindings())
                    ->orWhereRaw('EXISTS ('.$rentPendingExistsSql->toSql().')',  $rentPendingExistsSql->getBindings());
                });
            }
        }
        // ---------- Orden ----------
        // Campos ordenables admitidos
        $sortable = ['id', 'period_value', 'rent_status', 'rent_date', 'rent_amount'];
        if (!in_array($sortBy, $sortable, true)) {
            $sortBy = 'id';
        }

        if ($sortBy === 'id') {
            $q->orderBy('contracts.id', $sortDirection);
        } elseif ($sortBy === 'period_value') {
            $q->orderByRaw('period_value '.$sortDirection);
        } elseif ($sortBy === 'rent_status') {
            $q->orderByRaw('rent_status '.$sortDirection);
        } elseif ($sortBy === 'rent_date') {
            // Orden por fecha de la renta dentro del JSON
            $q->orderByRaw("JSON_EXTRACT(rent_info, '$.effective_date') ".$sortDirection);
        } elseif ($sortBy === 'rent_amount') {
            $q->orderByRaw("CAST(JSON_EXTRACT(rent_info, '$.amount') AS DECIMAL(18,2)) ".$sortDirection);
        } else {
            $q->orderBy('contracts.id');
        }

        // ---------- Paginación ----------
        $result = $q->paginate($perPage);

        return response()->json([
            'period' => $period->format('Y-m'),
            'from'   => $from,
            'to'     => $to,
            'data'   => $result->items(),
            'meta'   => [
                'current_page' => $result->currentPage(),
                'from'         => $result->firstItem(),
                'to'           => $result->lastItem(),
                'per_page'     => $result->perPage(),
                'total'        => $result->total(),
                'last_page'    => $result->lastPage(),
            ],
        ]);
    }

    public function summary(Request $request)
    {
        // Periodo
        $periodStr = $request->input('period'); // 'YYYY-MM'
        $period = normalizePeriodOrFail($periodStr);
        if (!$period) {
            return response()->json(['message' => 'Parámetro period requerido (YYYY-MM)'], 422);
        }

        $from = $period->copy()->startOfMonth()->toDateString();
        $to   = $period->copy()->startOfMonth()->addMonth()->toDateString();

        // Tipo de cargo "RENT"
        $rentTypeId = ChargeType::where('code', 'RENT')->value('id');
        if (!$rentTypeId) {
            return response()->json(['message' => "No se encontró ChargeType con code=RENT"], 422);
        }

        // ---- Subqueries necesarias (solo ajustes pendientes y existencia de renta) ----
        $pendingAdjExistsSub = ContractAdjustment::query()
            ->selectRaw('1')
            ->whereColumn('contract_id', 'contracts.id')
            ->whereNull('applied_at')
            ->where('effective_date', '<', $to) // incluye pendientes arrastrados
            ->limit(1);

        $rentExistsSub = ContractCharge::query()
            ->selectRaw('1')
            ->whereColumn('contract_id', 'contracts.id')
            ->where('charge_type_id', $rentTypeId)
            ->where('effective_date', '>=', $from)
            ->where('effective_date', '<',  $to)
            ->limit(1);

        // ---- Base por contrato (flags) ----
        $base = Contract::query()
            ->activeDuring($period)
            ->select('contracts.id')
            ->selectRaw(
                'EXISTS ('.$pendingAdjExistsSub->toSql().') AS has_pending_adj',
                $pendingAdjExistsSub->getBindings()
            )
            ->selectRaw(
                'EXISTS ('.$rentExistsSub->toSql().') AS has_rent',
                $rentExistsSub->getBindings()
            );

        // ---- Agregación ----
        $row = DB::query()
            ->fromSub($base, 'base')
            ->selectRaw('COUNT(*) AS active_contracts')
            ->selectRaw('SUM(has_pending_adj) AS pending_adjustments')
            ->selectRaw('SUM(has_rent) AS rents_generated')
            ->selectRaw('SUM(CASE WHEN has_pending_adj = 0 AND has_rent = 0 THEN 1 ELSE 0 END) AS pending_generation')
            ->first();

        // Derivados mínimos
        $activeContracts    = (int) ($row->active_contracts ?? 0);
        $pendingAdjustments = (int) ($row->pending_adjustments ?? 0);
        $rentsGenerated     = (int) ($row->rents_generated ?? 0);
        $pendingGeneration  = (int) ($row->pending_generation ?? 0);

        $coverageDen   = max(0, $activeContracts - $pendingAdjustments);
        $coverageRatio = $coverageDen > 0 ? round($rentsGenerated / $coverageDen, 4) : null;

        return response()->json([
            'period' => $period,
            'from'   => $from,
            'to'     => $to,
            'totals' => [
                'active_contracts'    => $activeContracts,
                'pending_adjustments' => $pendingAdjustments,
                'rents_generated'     => $rentsGenerated,
                'pending_generation'  => $pendingGeneration,
                'coverage_ratio'      => $coverageRatio, // 0..1 (formateá en % en el front)
            ],
        ]);
    }









    public function generateAll(GenerateRentRequest $request)
    {
        $periodStr = $request->input('period');
        $period = Carbon::parse(strlen($periodStr) === 7 ? $periodStr.'-01' : $periodStr)->startOfMonth();

        $ids = (array) $request->input('contract_ids', []);
        $dryRun = $request->boolean('dry_run', false);

        if (empty($ids)) {
            // Backward compatibility: generate for all active contracts
            $summary = $this->service->generateForMonth($period, $dryRun);
            return response()->json(['period' => $period->format('Y-m'), 'summary' => $summary]);
        }

        $calculator = app(ContractRentCalculator::class);
        $results = [];
        $created = [];

        DB::transaction(function () use (&$results, &$created, $ids, $period, $calculator, $dryRun) {
            $contracts = Contract::query()->whereIn('id', $ids)->get();
            $effectiveDate = $period->copy()->startOfMonth()->toDateString();
            $rentTypeId = ChargeType::where('code', 'RENT')->value('id');

            foreach ($contracts as $contract) {
                $cur = $contract->currency ?? 'ARS';
                try {
                    // checks
                    $hasBlockingAdj = $contract->adjustments()
                        ->whereDate('effective_date', '<=', $effectiveDate)
                        ->where(function ($qq) { $qq->whereNull('applied_at')->orWhereNull('applied_amount'); })
                        ->exists();
                    if ($hasBlockingAdj) {
                        $results[] = [
                            'contract_id' => $contract->id,
                            'currency' => $cur,
                            'status' => 'skipped',
                            'reason_code' => 'pending_adjustment',
                            'reason_detail' => 'Falta aplicar ajuste vigente',
                        ];
                        continue;
                    }

                    // $blocked = $contract->insurance_required && !$contract->attachments()->exists();
                    $blocked = false;
                    if ($blocked) {
                        $results[] = [
                            'contract_id' => $contract->id,
                            'currency' => $cur,
                            'status' => 'skipped',
                            'reason_code' => 'blocked_by_documentation',
                            'reason_detail' => 'Seguro/documentación vencida',
                        ];
                        continue;
                    }
                    \Log::info('generateAll', ['step' => '1']);
                    $hasDraft = ContractCharge::query()
                        ->where('contract_id', $contract->id)
                        ->where('charge_type_id', $rentTypeId)
                        ->whereDate('effective_date', $effectiveDate)
                        ->exists();

                    if ($hasDraft) {
                        $results[] = [
                            'contract_id' => $contract->id,
                            'currency' => $cur,
                            'status' => 'skipped',
                            'reason_code' => 'generated_draft_exists',
                            'reason_detail' => 'Ya existe renta borrador para el período',
                        ];
                        continue;
                    }

                    // compute amounts
                    $baseAdj = $calculator->monthlyBaseFor($contract, $period);
                    $amount = $calculator->applyProrationIfNeeded($contract, $period, $baseAdj);

                    if ($dryRun) {
                        $results[] = [
                            'contract_id' => $contract->id,
                            'currency' => $cur,
                            'status' => 'skipped',
                            'reason_code' => 'dry_run',
                            'reason_detail' => 'Ejecución en modo simulación',
                            'amount_base' => $baseAdj,
                            'amount_adjusted' => $amount,
                        ];
                        continue;
                    }

                    $res = $this->service->ensureRentCharge($contract, $period);
                    $generated = ($res['created'] ?? 0) > 0 || ($res['updated'] ?? 0) > 0;

                    if ($generated) {
                        $created[$cur]['count'] = ($created[$cur]['count'] ?? 0) + 1;
                        $created[$cur]['total_base'] = ($created[$cur]['total_base'] ?? 0) + $baseAdj;
                        $created[$cur]['total_adjusted'] = ($created[$cur]['total_adjusted'] ?? 0) + $amount;
                        $results[] = [
                            'contract_id' => $contract->id,
                            'currency' => $cur,
                            'status' => 'generated',
                            'amount_base' => $baseAdj,
                            'amount_adjusted' => $amount,
                        ];
                    } else {
                        $results[] = [
                            'contract_id' => $contract->id,
                            'currency' => $cur,
                            'status' => 'skipped',
                            'reason_code' => 'no_change',
                            'reason_detail' => 'Sin cambios respecto al período',
                            'amount_base' => $baseAdj,
                            'amount_adjusted' => $amount,
                        ];
                    }
                } catch (\Throwable $e) {
                    $results[] = [
                        'contract_id' => $contract->id,
                        'currency' => $cur,
                        'status' => 'skipped',
                        'reason_code' => 'unknown_error',
                        'reason_detail' => $e->getMessage(),
                    ];
                }
            }
        });

        return response()->json([
            'period' => $period->format('Y-m'),
            'created' => $created,
            'results' => $results,
        ]);
    }

    public function generateForContract(GenerateRentRequest $request, Contract $contract): JsonResponse
    {
        $period = Carbon::parse($request->input('period'))->startOfMonth();
        $summary = $this->service->generateForContract($contract, $period, $request->boolean('dry_run', false));
        return response()->json(['summary' => $summary]);
    }


}
