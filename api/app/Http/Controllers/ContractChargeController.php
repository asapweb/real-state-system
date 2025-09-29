<?php

namespace App\Http\Controllers;

use App\Http\Resources\ContractChargeResource;
use App\Http\Requests\CancelContractChargeRequest;
use App\Http\Requests\StoreContractChargeRequest;
use App\Http\Requests\UpdateContractChargeRequest;
use App\Models\ContractCharge;
use App\Models\ChargeType;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use DomainException;

class ContractChargeController extends Controller
{
    /** Lista paginada por contrato (filtros básicos) */
    public function index(Request $request)
    {
        // Periodo
        $periodStr = $request->input('period'); // 'YYYY-MM'
        $period = Carbon::createFromFormat('Y-m', $periodStr);
        $from = $period->copy()->startOfMonth()->toDateString();
        $to   = $period->copy()->startOfMonth()->addMonth()->toDateString();


        $query = ContractCharge::query()
            ->with(['chargeType','counterparty','serviceType', 'contract.mainTenant.client', 'contract.clients.client', 'canceledBy', 'tenantLiquidationVoucher'])
            ->when($request->contract_id, fn($q, $id) => $q->where('contract_id', $id))
            ->when($request->type_code, function ($q, $code) {
                $q->whereHas('chargeType', fn($qq) => $qq->where('code', $code));
            })
            ->when($request->period, function  ($q) use ($from, $to) {
                $q->where('effective_date', '>=', $from)
                ->where('effective_date', '<', $to);
            })
            ->when($request->get('status'), function ($q, $status) {
                if ($status === 'canceled') {
                    $q->canceled();
                } elseif ($status === 'all') {
                    // sin filtro adicional
                } else {
                    $q->active();
                }
            }, function ($q) {
                $q->active();
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
            new ContractChargeResource($charge->load(['chargeType','counterparty','serviceType'])),
            Response::HTTP_CREATED
        );
    }

    /** Ver cargo */
    public function show(ContractCharge $contractCharge)
    {
        $contractCharge->load(['chargeType','counterparty','serviceType','canceledBy']);

        return new ContractChargeResource($contractCharge);
    }

    /** Actualizar cargo */
    public function update(UpdateContractChargeRequest $request, ContractCharge $contractCharge)
    {
        $data = $request->validated();

        $financialFields = [
            'service_type_id',
            'counterparty_contract_client_id',
            'amount',
            'currency',
            'effective_date',
            'due_date',
            'service_period_start',
            'service_period_end',
            'invoice_date',
        ];

        $lockedByVoucher = $contractCharge->hasLockedLiquidationVoucher();
        $isCanceled = $contractCharge->is_canceled;

        if ($lockedByVoucher || $isCanceled) {
            $changed = array_filter($financialFields, function ($field) use ($data, $contractCharge) {
                if (!array_key_exists($field, $data)) {
                    return false;
                }

                $newValue = $data[$field];
                $currentValue = $contractCharge->{$field};

                if ($currentValue instanceof \DateTimeInterface) {
                    $currentValue = $currentValue->format('Y-m-d');
                }

                if ($newValue instanceof \DateTimeInterface) {
                    $newValue = $newValue->format('Y-m-d');
                }

                if (in_array($field, ['service_type_id', 'counterparty_contract_client_id'], true)) {
                    $currentValue = $currentValue ? (int) $currentValue : null;
                    $newValue = $newValue !== null ? (int) $newValue : null;
                } elseif ($field === 'amount') {
                    $currentValue = $currentValue !== null ? (float) $currentValue : null;
                    $newValue = $newValue !== null ? (float) $newValue : null;
                } elseif (in_array($field, ['currency'], true)) {
                    $currentValue = $currentValue ? strtoupper((string) $currentValue) : $currentValue;
                    $newValue = $newValue ? strtoupper((string) $newValue) : $newValue;
                }

                return $currentValue !== $newValue;
            });

            if (!empty($changed)) {
                $message = $isCanceled
                    ? 'El cargo está cancelado y no permite modificar datos financieros.'
                    : 'El cargo está asentado en una liquidación y no permite modificar datos financieros.';

                return response()->json([
                    'message' => $message,
                    'errors' => [
                        'fields' => array_values($changed),
                    ],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $editableFields = ['description'];

        if (!$lockedByVoucher && !$isCanceled) {
            $editableFields = array_merge($financialFields, ['description']);
        }

        $payload = Arr::only($data, $editableFields);

        if (array_key_exists('currency', $payload)) {
            $payload['currency'] = strtoupper($payload['currency']);
        }

        $contractCharge->fill($payload);
        $contractCharge->save();

        return new ContractChargeResource($contractCharge->load(['chargeType','counterparty','serviceType','canceledBy']));
    }

    /** Borrar cargo (en dev puede ser hard delete; en prod podrías hacer soft-delete) */
    public function destroy(ContractCharge $contractCharge)
    {
        $contractCharge->delete();
        return response()->noContent();
    }

    public function cancel(CancelContractChargeRequest $request, ContractCharge $contractCharge)
    {
        if ($contractCharge->is_canceled) {
            return response()->json([
                'message' => 'El cargo ya se encuentra cancelado.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $request->user() ?? auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Acceso no autorizado.',
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            DB::transaction(function () use ($contractCharge, $request, $user) {
                $contractCharge->cancel($request->validated()['reason'], $user);
            });
        } catch (DomainException $exception) {
            if ($exception->getMessage() === 'has_non_draft_voucher') {
                return response()->json([
                    'message' => 'No se puede cancelar el cargo porque está asentado en una liquidación confirmada.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return response()->json([
                'message' => 'No se pudo cancelar el cargo.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $contractCharge->load(['chargeType','counterparty','serviceType','canceledBy']);

        return new ContractChargeResource($contractCharge);
    }
}
