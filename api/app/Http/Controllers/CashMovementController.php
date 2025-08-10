<?php

namespace App\Http\Controllers;

use App\Models\CashMovement;
use App\Models\CashAccount;
use App\Http\Resources\CashMovementResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class CashMovementController extends Controller
{
    public function index(Request $request)
    {
        $query = CashMovement::query();
        if ($request->filled('cash_account_id')) {
            $query->where('cash_account_id', $request->input('cash_account_id'));
        }
        if ($request->filled('direction')) {
            $query->where('direction', $request->input('direction'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->input('date_to'));
        }
        if ($request->filled('currency')) {
            $query->where('currency', $request->input('currency'));
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('concept', 'like', '%' . $request->input('search') . '%')
                  ->orWhere('reference', 'like', '%' . $request->input('search') . '%');
            });
        }
        $query->with(['cashAccount', 'voucher']);
        $query->orderByDesc('date');
        $movements = $query->paginate($request->input('per_page', 10));
        return CashMovementResource::collection($movements);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cash_account_id' => 'required|exists:cash_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|in:ARS,USD',
            'direction' => ['required', Rule::in(['in', 'out'])],
            'concept' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
            'date' => 'nullable|date',
        ]);

        $account = CashAccount::findOrFail($validated['cash_account_id']);
        
        // Verificar que la moneda del movimiento coincida con la de la cuenta
        if ($account->currency !== $validated['currency']) {
            return response()->json(['message' => 'La moneda del movimiento debe coincidir con la moneda de la cuenta.'], 422);
        }
        
        if ($validated['direction'] === 'out') {
            $balance = $account->balance;
            if ($balance < $validated['amount']) {
                return response()->json(['message' => 'Saldo insuficiente en la cuenta.'], 422);
            }
        }

        $movement = DB::transaction(function () use ($validated) {
            return CashMovement::create([
                'cash_account_id' => $validated['cash_account_id'],
                'amount' => $validated['amount'],
                'currency' => $validated['currency'],
                'direction' => $validated['direction'],
                'concept' => $validated['concept'] ?? null,
                'reference' => $validated['reference'] ?? null,
                'date' => $validated['date'] ?? now(),
            ]);
        });
        return new CashMovementResource($movement);
    }

    public function show(CashMovement $cashMovement)
    {
        return new CashMovementResource($cashMovement->load([
            'cashAccount',
            'paymentMethod',
            'voucher',
        ]));

    }

    public function destroy(CashMovement $cashMovement)
    {
        if ($cashMovement->voucher_id) {
            return response()->json(['message' => 'No se puede revertir un movimiento asociado a un comprobante.'], 422);
        }
        $cashMovement->delete();
        return response()->noContent();
    }
}
