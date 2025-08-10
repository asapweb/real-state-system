<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractExpense;
use App\Enums\ContractExpenseStatus;
use App\Models\VoucherType;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use App\Http\Resources\CollectionResource;
use App\Services\ContractBillingService;
use App\Services\VoucherGenerationService;
use App\Services\VoucherService;
use DB;

class CollectionController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'period' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'status' => ['nullable', 'in:pending,draft,issued'],
            'contract_id' => ['nullable', 'exists:contracts,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'currency' => ['nullable', 'string', 'in:ARS,USD'],
            'page' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer'],
        ]);

        $period = normalizePeriodOrFail($validated['period']);

        $query = Contract::query()
            ->with(['mainTenant.client'])
            ->where(function ($q) use ($period) {
                $q->whereDate('start_date', '<=', $period)
                ->where(function ($q2) use ($period) {
                    $q2->whereNull('end_date')
                        ->orWhereDate('end_date', '>=', $period);
                });
            });

        if (!empty($validated['contract_id'])) {
            $query->where('id', $validated['contract_id']);
        }
        if (!empty($validated['client_id'])) {
            $query->whereHas('mainTenant', fn($q) => $q->where('client_id', $validated['client_id']));
        }
        if (!empty($validated['currency'])) {
            $query->where('currency', $validated['currency']);
        }

        $perPage = $validated['per_page'] ?? 10;

        return CollectionResource::collection(
            $query->paginate($perPage)->through(fn($contract) => new CollectionResource($contract, $period))

        );
    }

    public function editor(Request $request, Contract $contract)
    {
        $period = normalizePeriodOrFail($request->period);

        $billing = app(ContractBillingService::class)->getBillingPreview($contract, $period);

        // Buscar si ya existe un voucher (emitido o draft) que contenga la renta
        $existingVoucherWithRent = $contract->vouchers()
            ->where('period', $period)
            ->where('currency', $contract->currency)
            ->whereIn('status', ['draft', 'issued'])
            ->whereHas('items', function ($query) {
                $query->where('type', 'rent');
            })
            ->first();

        $hasRentItem = $existingVoucherWithRent !== null;
        $voucherId = $existingVoucherWithRent?->id;
        $voucherStatus = $existingVoucherWithRent?->status;

        $rentItem = [
        'id' => 'rent',
        'type' => 'rent',
        'description' => "Renta {$period}",
        'amount' => $billing['rent'],
        'currency' => $contract->currency,
        'voucher_id' => $voucherId,
        'included_in_voucher' => $hasRentItem,
        'locked' => $hasRentItem,
        'state_label' => $hasRentItem
            ? "Incluido en Voucher #{$voucherId}" . ($voucherStatus === 'issued' ? " (emitido)" : "")
            : 'Pendiente',
        'selected' => !$hasRentItem,
        ];


        $expensesByCurrency = $billing['expenses'];

        // Armar lista completa por moneda
        $merged = collect($expensesByCurrency)
            ->map(function ($group) use ($rentItem) {
                if ($group['currency'] === $rentItem['currency']) {
                    array_unshift($group['expenses'], $rentItem);
                    $group['amount'] = round($group['amount'] + $rentItem['amount'], 2);
                }
                // Transformar cada ítem a formato estándar
                $group['expenses'] = collect($group['expenses'])->map(function ($expense) {
                    return [
                        'id' => ($expense['id'] !== 'rent') ? 'expense-' . $expense['id'] : 'rent',
                        'type' => ($expense['id'] !== 'rent') ? 'expense' : 'rent',
                        'description' => $expense['description'],
                        'amount' => $expense['amount'],
                        'currency' => $expense['currency'],
                        'voucher_id' => $expense['voucher_id'] ?? null,
                        'included_in_voucher' => $expense['included_in_voucher'] ?? false,
                        'locked' => $expense['voucher_id'] !== null,
                        'state_label' => $expense['voucher_id']
                            ? "Incluido en Voucher #{$expense['voucher_id']}"
                            : 'Pendiente',
                        'selected' => $expense['voucher_id'] === null,
                    ];
                })->toArray();

                return $group;
            })
            ->values();

        // Si la moneda de la renta no aparece en gastos, agregarla como grupo
        if (!$merged->pluck('currency')->contains($rentItem['currency'])) {
            $merged->push([
                'currency' => $rentItem['currency'],
                'amount' => $rentItem['amount'],
                'expenses' => [$rentItem],
            ]);
        }

        return response()->json([
            'contract' => [
                'id' => $contract->id,
                'main_tenant' => $contract->mainTenant,
                'currency' => $contract->currency,
            ],
            'period' => $period,
            'status' => app(ContractBillingService::class)
                ->determineStatus($contract, $period, $billing['pending_adjustment']),
            'expenses' => $merged,
        ]);
    }


    public function view(Request $request, int $contractId)
    {
        $validated = $request->validate([
            'period' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        $period = normalizePeriodOrFail($validated['period']);

        $contract = Contract::with(['mainTenant.client'])->findOrFail($contractId);

        $billing = app(ContractBillingService::class)->getBillingPreview($contract, $period);

        $vouchers = $contract->vouchers()
            ->with(['items', 'payments', 'applications.appliedTo', 'voucherType'])
            ->whereDate('period', $period->toDateString())
            ->orderBy('created_at')
            ->get();

        $status = app(ContractBillingService::class)->determineStatus($contract, $period, $billing['pending_adjustment']);

        return response()->json([
            'contract' => [
                'id' => $contract->id,
                'currency' => $contract->currency,
                'main_tenant' => [
                    'client' => [
                        'full_name' => $contract->mainTenant?->client?->full_name,
                    ],
                ],
            ],
            'status' => $status,
            'vouchers' => $vouchers->map(function ($voucher) {
                return [
                    'id' => $voucher->id,
                    'currency' => $voucher->currency,
                    'status' => $voucher->status,
                    'voucher_type_short_name' => $voucher->voucherType->short_name,
                    'voucher_type_letter' => $voucher->voucherType->letter,
                    'full_number' => $voucher->full_number,
                    'total' => $voucher->total,
                    'items' => $voucher->items->map(fn ($i) => [
                        'description' => $i->description,
                        'quantity' => $i->quantity,
                        'unit_price' => $i->unit_price,
                        'subtotal' => $i->subtotal,
                    ]),
                    'payments' => $voucher->payments->map(fn ($p) => [
                        'amount' => $p->amount,
                        'method' => $p->method,
                    ]),
                    'applications' => $voucher->applications->map(fn ($a) => [
                        'applied_to_id' => $a->applied_to_id,
                        'amount' => $a->amount,
                    ]),
                ];
            }),
        ]);
    }


    /**
     * Genera la cobranza del período para un contrato específico.
     */
    public function generate(Request $request, Contract $contract)
    {
        $period = normalizePeriodOrFail($request->period);

        app(VoucherGenerationService::class)->generateFromEditor(
            $contract,
            $period,
            $request->items
        );

        return response()->json(['message' => 'Cobranza actualizada correctamente']);
    }

}
