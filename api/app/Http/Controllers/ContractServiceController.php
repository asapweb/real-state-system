<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractService;
use Illuminate\Http\Request;
use App\Http\Requests\StoreContractServiceRequest;
use App\Http\Requests\UpdateContractServiceRequest;
use App\Http\Resources\ContractServiceResource;

class ContractServiceController extends Controller
{
    public function index(Request $request, Contract $contract)
    {
        // Parámetros de paginación y ordenamiento
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'id');
        $sortDirection = strtolower($request->input('sort_direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'service_type', 'provider_name', 'owner_name', 'account_number', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        // Consulta base
        $query = $contract->services();

        // Filtros
        if ($request->filled('search.service_type')) {
            $query->where('service_type', $request->search['service_type']);
        }

        if ($request->filled('search.text')) {
            $text = $request->search['text'];
            $query->where(function ($q) use ($text) {
                $q->where('provider_name', 'like', "%$text%")
                  ->orWhere('owner_name', 'like', "%$text%")
                  ->orWhere('account_number', 'like', "%$text%");
            });
        }

        $query->orderBy($sortBy, $sortDirection);
        return ContractServiceResource::collection($query->paginate($perPage));
    }

    public function store(StoreContractServiceRequest $request, Contract $contract)
    {
        $data = $request->validated();
        $data['contract_id'] = $contract->id;

        $service = ContractService::create($data);

        return response()->json($service, 201);
    }

    public function show(ContractService $contractService)
    {
        return response()->json($contractService);
    }

    public function update(UpdateContractServiceRequest $request, Contract $contract, ContractService $contractService)
    {
        $contractService->update($request->validated());

        return response()->json($contractService);
    }

    public function destroy(Contract $contract,ContractService $contractService)
    {
        $contractService->delete();

        return response()->json(['message' => 'Servicio eliminado correctamente.']);
    }
}
