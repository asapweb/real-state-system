<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractClient;
use App\Http\Requests\StoreContractClientRequest;
use App\Http\Requests\UpdateContractClientRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Resources\ContractClientResource;

class ContractClientController extends Controller
{
    public function index(Request $request, Contract $contract)
    {
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'id');
        $sortDirection = strtolower($request->input('sort_direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'created_at', 'role'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        $query = $contract->clients()->with('client');

        if ($request->filled('search.client')) {
            $query->whereHas('client', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search['client'] . '%')
                ->orWhere('last_name', 'like', '%' . $request->search['client'] . '%');
            });
        }

        $query->orderBy($sortBy, $sortDirection);

        return ContractClientResource::collection($query->paginate($perPage));
    }

    public function store(StoreContractClientRequest $request, Contract $contract)
    {
        $data = $request->validated();
        $data['contract_id'] = $contract->id;

        $client = ContractClient::create($data);

        return response()->json($client->load('client'), 201);
    }

    public function show(ContractClient $contractClient)
    {
        return response()->json($contractClient->load('client'));
    }

    public function update(UpdateContractClientRequest $request, Contract $contract, ContractClient $contractClient)
    {
        if ($contractClient->contract_id !== $contract->id) {
            return response()->json(['message' => 'El cliente no pertenece a este contrato.'], 403);
        }

        $contractClient->update($request->validated());

        return response()->json($contractClient->load('client'));
    }

    public function destroy(Contract $contract, ContractClient $contractClient)
    {
        if ($contractClient->contract_id !== $contract->id) {
            return response()->json(['message' => 'El cliente no pertenece a este contrato.'], 403);
        }

        $contractClient->delete();

        return response()->json(['message' => 'Cliente eliminado del contrato.']);
    }
}
