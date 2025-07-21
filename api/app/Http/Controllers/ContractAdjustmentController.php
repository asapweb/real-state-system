<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractAdjustment;
use Illuminate\Http\Request;
use App\Http\Requests\StoreContractAdjustmentRequest;
use App\Http\Requests\UpdateContractAdjustmentRequest;
use App\Http\Resources\ContractAdjustmentResource;
use App\Services\ContractAdjustmentService;

class ContractAdjustmentController extends Controller
{
    protected ContractAdjustmentService $contractAdjustmentService;

    public function __construct(ContractAdjustmentService $contractAdjustmentService)
    {
        $this->contractAdjustmentService = $contractAdjustmentService;
    }

    public function globalIndex(Request $request)
    {
        $query = ContractAdjustment::with('contract.clients.client', 'indexType');

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

    public function apply(Contract $contract, ContractAdjustment $adjustment)
    {
        if ($adjustment->contract_id !== $contract->id) {
            return response()->json(['message' => 'El ajuste no pertenece al contrato.'], 403);
        }

        try {
            $this->contractAdjustmentService->apply($adjustment);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        $adjustment->load(['contract.clients.client', 'indexType', 'attachments']);

        return new ContractAdjustmentResource($adjustment);
    }
}
