<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContractRequest;
use App\Http\Requests\UpdateContractRequest;
use App\Models\Contract;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        // Paginación y orden
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'id');
        $sortDirection = strtolower($request->input('sort_direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'start_date', 'end_date', 'monthly_amount', 'status', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        $query = Contract::query();

        // Filtros
        if ($request->filled('search.property_id')) {
            $query->where('property_id', $request->search['property_id']);
        }

        if ($request->filled('search.status')) {
            $query->where('status', $request->search['status']);
        }

        if ($request->has('search.text')) {
            $text = $request->search['text'];
            $query->whereHas('property', function ($q) use ($text) {
                $q->where('street', 'like', "%$text%")
                  ->orWhere('registry_number', 'like', "%$text%");
            });
        }

        $query->with(['property', 'rentalApplication']);

        $query->orderBy($sortBy, $sortDirection);

        return response()->json($query->paginate($perPage));
    }

    public function store(StoreContractRequest $request)
    {
        $contract = Contract::create($request->validated());

        return response()->json($contract->load(['property', 'rentalApplication']), 201);
    }

    public function show(Contract $contract)
    {
        return response()->json($contract->load(['property', 'rentalApplication', 'attachments']));
    }

    public function update(UpdateContractRequest $request, Contract $contract)
    {
        $contract->update($request->validated());

        return response()->json($contract->load(['property', 'rentalApplication']));
    }

    public function destroy(Contract $contract)
    {
        $contract->delete();

        return response()->json(['message' => 'Contrato eliminado correctamente.']);
    }
}
