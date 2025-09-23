<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractAdjustment;
use Illuminate\Http\Request;
use App\Http\Requests\StoreContractAdjustmentRequest;
use App\Http\Requests\UpdateContractAdjustmentRequest;
use App\Http\Resources\ContractAdjustmentResource;
use App\Services\ContractAdjustmentService;
use App\Enums\ContractAdjustmentType;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class ContractAdjustmentController extends Controller
{
    protected ContractAdjustmentService $contractAdjustmentService;

    public function __construct(ContractAdjustmentService $contractAdjustmentService)
    {
        $this->contractAdjustmentService = $contractAdjustmentService;
    }

    public function summary(Request $request): JsonResponse
    {
        $periodStr = $request->query('period');
        if (!$periodStr) {
            return response()->json(['message' => 'Parámetro period requerido (YYYY-MM)'], 422);
        }

        $period = normalizePeriodOrFail($periodStr);
        $startOfMonth = $period->copy()->startOfMonth()->toDateString();
        $endOfMonth = $period->copy()->endOfMonth()->toDateString();

        $baseQuery = Contract::query()->activeDuring($period);

        $totals = [];
        $totals['active_contracts'] = (clone $baseQuery)->count();

        // Single query to get all adjustment statistics for the period
        $adjustmentStats = ContractAdjustment::query()
            ->whereBetween('effective_date', [$startOfMonth, $endOfMonth])
            ->selectRaw('
                COUNT(*) as total_adjustments,
                COUNT(CASE WHEN value IS NULL THEN 1 END) as adjustments_without_value,
                COUNT(CASE WHEN applied_at IS NULL THEN 1 END) as adjustments_not_applied,
                COUNT(CASE WHEN applied_at IS NULL THEN 1 END) as adjustments_not_applied
            ')
            ->first();

        $totals['adjustments'] = $adjustmentStats->total_adjustments ?? 0;
        $totals['adjustments_pending_value'] = $adjustmentStats->adjustments_without_value ?? 0;
        $totals['adjustments_pending_apply'] = $adjustmentStats->adjustments_not_applied ?? 0;

        return response()->json([
            'period' => $period->format('Y-m'),
            'totals' => $totals,
        ]);
    }


    public function globalIndex(Request $request)
    {
        $query = ContractAdjustment::with('contract.mainTenant.client', 'contract.clients.client', 'indexType');

        if ($request->filled('contract_id')) {
            $query->where('contract_id', $request->input('contract_id'));
        }

        if ($request->filled('client_id')) {
            $query->whereHas('contract.clients', function ($q) use ($request) {
                $q->where('client_id', $request->input('client_id'));
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('effective_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('effective_date', '<=', $request->input('date_to'));
        }

        if ($request->filled('effective_date_from')) {
            $query->whereDate('effective_date', '>=', $request->input('effective_date_from'));
        }

        if ($request->filled('effective_date_to')) {
            $query->whereDate('effective_date', '<=', $request->input('effective_date_to'));
        }

        if ($request->filled('period')) {
            $period = normalizePeriodOrFail($request->input('period'));
            $startOfMonth = $period->copy()->startOfMonth()->toDateString();
            $endOfMonth = $period->copy()->endOfMonth()->toDateString();
            $query->whereBetween('effective_date', [$startOfMonth, $endOfMonth]);
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            $today = now()->startOfDay();

            $query->where(function ($q) use ($status, $today) {
                match ($status) {
                    'pending' => $q->whereDate('effective_date', '>', $today),
                    'expired_without_value' => $q->whereDate('effective_date', '<=', $today)->whereNull('value'),
                    'with_value' => $q->whereNotNull('value')->whereNull('applied_at'),
                    'applied' => $q->whereNotNull('applied_at'),
                    default => null,
                };
            });
        }

        $perPage = $request->input('per_page', 15);
        $sortBy = $request->input('sort_by', 'effective_date');
        $sortDirection = strtolower($request->input('sort_direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'effective_date', 'type', 'value', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'effective_date';
        }

        $query->orderBy($sortBy, $sortDirection);

        return ContractAdjustmentResource::collection($query->paginate($perPage));
    }

    public function index(Request $request, Contract $contract)
    {
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'effective_date');
        $sortDirection = strtolower($request->input('sort_direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'effective_date', 'type', 'value', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'effective_date';
        }

        $query = $contract->adjustments()->with(['indexType', 'attachments']);

        $query->orderBy($sortBy, $sortDirection);

        return ContractAdjustmentResource::collection($query->paginate($perPage));
    }

    public function store(StoreContractAdjustmentRequest $request, Contract $contract)
    {
        $adjustment = new ContractAdjustment($request->validated());
        $adjustment->contract_id = $contract->id;
        $adjustment->save();

        $adjustment->load(['contract.clients.client', 'indexType', 'attachments']);

        return new ContractAdjustmentResource($adjustment);
    }

    public function show(Contract $contract, ContractAdjustment $adjustment)
    {
        if ($adjustment->contract_id !== $contract->id) {
            return response()->json(['message' => 'El ajuste no pertenece al contrato.'], 403);
        }

        $adjustment->load(['contract.clients.client', 'indexType', 'attachments']);

        return new ContractAdjustmentResource($adjustment);
    }

    public function update(UpdateContractAdjustmentRequest $request, Contract $contract, ContractAdjustment $adjustment)
    {
        if ($adjustment->contract_id !== $contract->id) {
            return response()->json(['message' => 'El ajuste no pertenece al contrato.'], 403);
        }

        if ($adjustment->applied_at !== null) {
            return response()->json(['message' => 'Este ajuste ya fue aplicado y no puede modificarse.'], 403);
        }

        $adjustment->update($request->validated());

        $adjustment->load(['contract.clients.client', 'indexType', 'attachments']);

        return new ContractAdjustmentResource($adjustment);
    }

    public function updateValue(Request $request, Contract $contract, ContractAdjustment $adjustment)
    {
        if ($adjustment->contract_id !== $contract->id) {
            return response()->json(['message' => 'El ajuste no pertenece al contrato.'], 403);
        }

        if ($adjustment->applied_at !== null) {
            return response()->json(['message' => 'Este ajuste ya fue aplicado y no puede modificarse.'], 403);
        }

        $validated = $request->validate([
            'value' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
            'applied_amount' => ['nullable', 'numeric'],
        ]);

        $adjustment->update($validated);

        $adjustment->load(['contract.clients.client', 'indexType', 'attachments']);

        return new ContractAdjustmentResource($adjustment);
    }

    public function destroy(Contract $contract, ContractAdjustment $adjustment)
    {
        if ($adjustment->contract_id !== $contract->id) {
            return response()->json(['message' => 'El ajuste no pertenece al contrato.'], 403);
        }

        if ($adjustment->applied_at !== null) {
            return response()->json(['message' => 'No se puede eliminar un ajuste ya aplicado.'], 403);
        }

        $adjustment->delete();

        return response()->json(['message' => 'Ajuste eliminado correctamente.']);
    }

    public function assignIndex(ContractAdjustment $adjustment, Request $request)
    {
        $this->contractAdjustmentService->assignIndexValue($adjustment);

        return response()->json([
            'message' => 'Valor de índice asignado correctamente.',
            'adjustment' => $adjustment->fresh(),
        ]);
    }


    public function apply(ContractAdjustment $adjustment, Request $request)
    {
        try {
            $this->contractAdjustmentService->apply($adjustment);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        $adjustment->load(['contract.clients.client', 'indexType', 'attachments']);

        return new ContractAdjustmentResource($adjustment);
    }

    public function assignIndexBulk(Request $request)
    {
        $validated = $request->validate([
            'period' => 'required|date_format:Y-m',
        ]);

        $adjustments = ContractAdjustment::where('effective_date', 'like', $validated['period'] . '%')->where('type', ContractAdjustmentType::INDEX)->get();

        $results = [
            'success' => [],
            'ignored' => [],
            'failed' => [],
        ];

        foreach ($adjustments as $adjustment) {
            if ($adjustment->value) {
                $results['ignored'][] = [
                    'id' => $adjustment->id,
                    'contract_id' => $adjustment->contract_id,
                    'message' => 'Ajuste ignorado: El índice ya tenía valor.',
                ];
                continue;
            }
            try {
                $this->contractAdjustmentService->assignIndexValue($adjustment);

                $results['success'][] = [
                    'id' => $adjustment->id,
                    'contract_id' => $adjustment->contract_id,
                    'message' => 'Índice asignado correctamente.',
                ];

            } catch (\Illuminate\Validation\ValidationException $e) {
                $message = collect($e->errors())->flatten()->first() ?? 'Error de validación al asignar índice.';
                $results['failed'][] = [
                    'id' => $adjustment->id,
                    'contract_id' => $adjustment->contract_id,
                    'message' => $message,
                ];
            } catch (\Throwable $e) {
                \Log::error('❌ Error inesperado al asignar índice', [
                    'adjustment_id' => $adjustment->id,
                    'exception' => $e,
                ]);

                $results['failed'][] = [
                    'id' => $adjustment->id,
                    'contract_id' => $adjustment->contract_id,
                    'message' => 'Error inesperado al asignar índice. Contacte al administrador.',
                ];
            }
        }

        return response()->json([
            'message' => 'Proceso de asignación de índices completado',
            'results' => $results,
            'total' => $adjustments->count(),
        ]);
    }

    public function applyBulk(Request $request)
    {
        $validated = $request->validate([
            'period' => 'required|date_format:Y-m',
        ]);

        $adjustments = ContractAdjustment::where('effective_date', 'like', $validated['period'] . '%')->get();

        $results = [
            'success' => [],
            'ignored' => [],
            'failed' => [],
        ];

        foreach ($adjustments as $adjustment) {
            if (is_null($adjustment->value)) {
                $results['failediled'][] = [
                    'id' => $adjustment->id,
                    'contract_id' => $adjustment->contract_id,
                    'message' => 'No tiene un valor asignado (pendiente de cálculo de índice).',
                ];
                continue;
            }

            if ($adjustment->applied_at) {
                $results['ignored'][] = [
                    'id' => $adjustment->id,
                    'contract_id' => $adjustment->contract_id,
                    'message' => 'Ajuste ignorado: el ajuste ya estaba aplicado.',
                ];
                continue;
            }

            try {
                $this->contractAdjustmentService->apply($adjustment);

                $results['success'][] = [
                    'id' => $adjustment->id,
                    'contract_id' => $adjustment->contract_id,
                    'message' => 'Ajuste aplicado correctamente.',
                ];
            } catch (\Illuminate\Validation\ValidationException $e) {
                $message = collect($e->errors())->flatten()->first() ?? 'Error de validación al aplicar ajuste.';
                $results['failed'][] = [
                    'id' => $adjustment->id,
                    'contract_id' => $adjustment->contract_id,
                    'message' => $message,
                ];
            } catch (\Throwable $e) {
                // Para errores inesperados, los capturamos como fallos genéricos
                \Log::error('❌ Error inesperado al aplicar ajuste', [
                    'adjustment_id' => $adjustment->id,
                    'exception' => $e,
                ]);

                $results['failed'][] = [
                    'id' => $adjustment->id,
                    'contract_id' => $adjustment->contract_id,
                    'message' => 'Error inesperado al aplicar el ajuste. Contacte al administrador.',
                ];
            }
        }

        return response()->json([
            'message' => 'Proceso de aplicación de ajustes completado',
            'results' => $results,
            'total' => $adjustments->count(),
        ]);
    }

    public function processBulk(Request $request)
    {
        $validated = $request->validate([
            'period' => 'required|date_format:Y-m',
        ]);

        $adjustments = ContractAdjustment::where('effective_date', 'like', $validated['period'] . '%')->get();

        $results = [
            'assigned' => ['success' => [], 'ignored' => [], 'failed' => []],
            'applied' => ['success' => [], 'ignored' => [], 'failed' => []],
        ];

        foreach ($adjustments as $adjustment) {
            if ($adjustment->type === ContractAdjustmentType::INDEX && is_null($adjustment->value)) {
                try {
                    $this->contractAdjustmentService->assignIndexValue($adjustment);
                    $results['assigned']['success'][] = [
                        'id' => $adjustment->id,
                        'contract_id' => $adjustment->contract_id,
                        'message' => 'Índice asignado correctamente.',
                    ];
                } catch (\Illuminate\Validation\ValidationException $e) {
                    $results['assigned']['failed'][] = [
                        'id' => $adjustment->id,
                        'contract_id' => $adjustment->contract_id,
                        'message' => collect($e->errors())->flatten()->first() ?? 'Error de validación al asignar índice.',
                    ];
                    continue; // ❌ No intentamos aplicar si falló la asignación
                }
            } else {
                $results['assigned']['ignored'][] = [
                    'id' => $adjustment->id,
                    'contract_id' => $adjustment->contract_id,
                    'message' => 'Ignorado en asignación: no es de tipo índice o ya tenía valor.',
                ];
            }

            // 🔹 2. Intentar aplicar el ajuste
            if (!$adjustment->applied_at && $adjustment->value !== null) {
                try {
                    $this->contractAdjustmentService->apply($adjustment);
                    $results['applied']['success'][] = [
                        'id' => $adjustment->id,
                        'contract_id' => $adjustment->contract_id,
                        'message' => 'Ajuste aplicado correctamente.',
                    ];
                } catch (\Illuminate\Validation\ValidationException $e) {
                    $results['applied']['failed'][] = [
                        'id' => $adjustment->id,
                        'contract_id' => $adjustment->contract_id,
                        'message' => collect($e->errors())->flatten()->first() ?? 'Error de validación al aplicar ajuste.',
                    ];
                }
            } else {
                $results['applied']['ignored'][] = [
                    'id' => $adjustment->id,
                    'contract_id' => $adjustment->contract_id,
                    'message' => 'Ignorado en aplicación: ya aplicado o sin valor asignado.',
                ];
            }
        }

        return response()->json([
            'message' => 'Proceso masivo de asignación y aplicación completado.',
            'results' => $results,
            'total' => $adjustments->count(),
        ]);
    }




}
