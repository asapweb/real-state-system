<?php

namespace App\Http\Controllers;

use App\Http\Resources\ContractChargeResource;
use App\Http\Requests\StoreContractChargeRequest;
use App\Http\Requests\UpdateContractChargeRequest;
use App\Models\ContractCharge;
use App\Models\ChargeType;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ContractChargeController extends Controller
{
    /** Lista paginada por contrato (filtros básicos) */
    public function index(Request $request)
    {
        $query = ContractCharge::query()
            ->with(['chargeType','counterparty', 'contract.mainTenant.client', 'contract.clients.client'])
            ->when($request->contract_id, fn($q, $id) => $q->where('contract_id', $id))
            ->when($request->type_code, function ($q, $code) {
                $q->whereHas('chargeType', fn($qq) => $qq->where('code', $code));
            })
            ->orderByDesc('effective_date')
            ->orderByDesc('id');

        $paginator = $query->paginate($request->integer('per_page', 25));

        return ContractChargeResource::collection($paginator);
    }



    /** Crear cargo */
    public function store(StoreContractChargeRequest $request)
    {
        $type = ChargeType::findOrFail($request->charge_type_id);

        // (Opcional) aplicar política de moneda CONTRACT_CURRENCY aquí
        // $currency = $type->currency_policy === ChargeType::CURR_CONTRACT
        //     ? $request->contract()->currency ?? $request->currency
        //     : $request->currency;

        $charge = ContractCharge::create([
            'contract_id'                     => $request->contract_id,
            'charge_type_id'                  => $request->charge_type_id,
            'service_type_id'                 => $request->service_type_id,
            'counterparty_contract_client_id' => $request->counterparty_contract_client_id,

            'amount'       => $request->amount, // siempre positivo
            'currency'     => strtoupper($request->currency),

            'effective_date'       => $request->effective_date,
            'due_date'             => $request->due_date,
            'service_period_start' => $request->service_period_start,
            'service_period_end'   => $request->service_period_end,
            'invoice_date'         => $request->invoice_date,

            'description' => $request->description,
        ]);

        return response()->json(
            $charge->load(['chargeType','counterparty']),
            Response::HTTP_CREATED
        );
    }

    /** Ver cargo */
    public function show(ContractCharge $contractCharge)
    {
        return $contractCharge->load(['chargeType','counterparty']);
    }

    /** Actualizar cargo */
    public function update(UpdateContractChargeRequest $request, ContractCharge $contractCharge)
    {
        // Si cambia el tipo, podrías revalidar requires_counterparty / service_period
        if ($request->has('charge_type_id') && $request->charge_type_id != $contractCharge->charge_type_id) {
            $type = ChargeType::findOrFail($request->charge_type_id);
            // (Opcional) agregar validación similar a Store para el nuevo tipo
        }

        $contractCharge->fill($request->only([
            'service_type_id',
            'counterparty_contract_client_id',
            'amount',
            'currency',
            'effective_date',
            'due_date',
            'service_period_start',
            'service_period_end',
            'invoice_date',
            'description',
        ]));

        if ($contractCharge->isDirty('currency')) {
            $contractCharge->currency = strtoupper($contractCharge->currency);
        }

        $contractCharge->save();

        return $contractCharge->load(['chargeType','counterparty']);
    }

    /** Borrar cargo (en dev puede ser hard delete; en prod podrías hacer soft-delete) */
    public function destroy(ContractCharge $contractCharge)
    {
        $contractCharge->delete();
        return response()->noContent();
    }
}
