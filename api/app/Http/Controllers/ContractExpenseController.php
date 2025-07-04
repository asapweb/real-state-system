<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractExpense;
use Illuminate\Http\Request;
use App\Http\Resources\ContractExpenseResource;

class ContractExpenseController extends Controller
{
    public function index(Request $request, Contract $contract)
    {
        $expenses = $contract->expenses()->paginate($request->input('per_page', 15));
        return ContractExpenseResource::collection($expenses);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'contract_id' => ['required', 'exists:contracts,id'],
            'due_date' => ['nullable', 'date'],
            'service_type' => ['required', 'string'],
            'amount' => ['required', 'numeric'],
            'currency' => ['required', 'string', 'size:3'],
            'period' => ['required', 'date'],
            'is_paid' => ['boolean'],
            'included_in_collection' => ['boolean'],
        ]);

        $expense = ContractExpense::create($data);
        return response()->json($expense, 201);
    }

    public function update(Request $request, Contract $contract, ContractExpense $expense)
{
    $validated = $request->validate([
        'service_type' => ['required', 'string'],
        'amount' => ['required', 'numeric'],
        'currency' => ['required', 'string'],
        'paid_by' => ['required', 'string'],
        'is_paid' => ['boolean'],
        'included_in_collection' => ['boolean'],
        'period' => ['required', 'date_format:Y-m'],
        'due_date' => ['nullable', 'date'],
        'description' => ['nullable', 'string'],
    ]);

    // Importante: asegurarse de que el gasto pertenece al contrato
    if ($expense->contract_id !== $contract->id) {
        abort(403, 'El gasto no pertenece al contrato.');
    }

    $expense->update($validated);

    return response()->json($expense->refresh());
}


    public function destroy(ContractExpense $contractExpense)
    {
        $contractExpense->delete();
        return response()->json(['message' => 'Eliminado correctamente']);
    }
}
