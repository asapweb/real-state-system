<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use App\Http\Resources\AccountMovementResource;
use Illuminate\Http\JsonResponse;
use App\Enums\Currency;

class AccountMovementController extends Controller
{
    public function index(Client $client, Request $request)
{
    // Requiere especificar una moneda
    if (!$request->filled('currency')) {
        return response()->json([
            'error' => 'Debe especificar una moneda para ver los movimientos de cuenta corriente'
        ], 400);
    }

    $currency = $request->currency;

    $query = $client->accountMovements()
        ->with('voucher.booklet.voucherType')
        ->where('currency', $currency)
        ->orderBy('date', 'asc')
        ->orderBy('created_at', 'asc');

    $movements = $query->get();

    // Calcular saldo acumulado
    $balance = 0;
    $movementsWithBalance = $movements->map(function ($movement) use (&$balance) {
        $balance += $movement->amount;
        $movement->running_balance = $balance;

        // Log opcional de trazabilidad
        logger()->debug('➡ Movimiento:');
        logger()->debug(' - Fecha: ' . $movement->date);
        logger()->debug(' - Comprobante: ' . ($movement->voucher->voucher_type_short_name ?? '') . ' ' . ($movement->voucher->full_number ?? ''));
        logger()->debug(' - Monto: ' . $movement->amount);
        logger()->debug(' - Saldo acumulado: ' . $movement->running_balance);

        return $movement;
    });

    // Paginar manualmente
    $perPage = $request->get('per_page', 25);
    $page = $request->get('page', 1);
    $offset = ($page - 1) * $perPage;
    $paginated = $movementsWithBalance->slice($offset, $perPage);

    return AccountMovementResource::collection(
        new \Illuminate\Pagination\LengthAwarePaginator(
            $paginated,
            $movements->count(),
            $perPage,
            $page,
            ['path' => $request->url()]
        )
    );
}



    /**
     * Get account balances by currency for a client
     */
    public function balances(Client $client): JsonResponse
    {
        $activeCurrencies = array_map(fn($c) => $c->value, Currency::cases());

        $balancesRaw = $client->accountMovements()
            ->selectRaw('currency, SUM(amount) as balance, COUNT(*) as movement_count')
            ->groupBy('currency')
            ->get()
            ->keyBy('currency');

        $balances = collect($activeCurrencies)->map(function ($currency) use ($balancesRaw) {
            $data = $balancesRaw->get($currency);
            return [
                'currency' => $currency,
                'balance' => $data ? (float) $data->balance : 0.0,
                'movement_count' => $data ? $data->movement_count : 0,
            ];
        })->values();

        return response()->json([
            'balances' => $balances,
            'default_currency' => Currency::ARS->value,
        ]);
    }
}
