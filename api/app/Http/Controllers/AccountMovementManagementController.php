<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use App\Models\AccountMovement;

class AccountMovementManagementController extends Controller
{
    public function setInitialBalance(Client $client, Request $request)
    {
        $request->validate([
            'currency' => 'required|string|size:3',
            'amount' => 'required|numeric',
        ]);

        $currency = $request->currency;
        $amount = $request->amount;

        $existing = $client->accountMovements()
            ->where('currency', $currency)
            ->where('is_initial', true)
            ->first();

        $hasLaterMovements = $client->accountMovements()
            ->where('currency', $currency)
            ->where('is_initial', false)
            ->exists();

        if ($existing && $hasLaterMovements) {
            return response()->json(['error' => 'No se puede modificar el saldo inicial porque ya existen movimientos.'], 422);
        }

        if ($existing) {
            $existing->update(['amount' => $amount]);
        } else {
            $client->accountMovements()->create([
                'date' => now()->startOfDay(),
                'description' => 'Saldo inicial',
                'amount' => $amount,
                'currency' => $currency,
                'is_initial' => true,
            ]);
        }

        return response()->json(['success' => true]);
    }
}
