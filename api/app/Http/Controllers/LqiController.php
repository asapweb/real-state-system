<?php

namespace App\Http\Controllers;

use App\Http\Requests\LqiSyncRequest;
use App\Http\Requests\LqiIssueRequest;
use App\Http\Requests\LqiReopenRequest;
use App\Models\Contract;
use App\Models\Voucher;
use App\Models\VoucherType;
use App\Services\LqiBuilderService;
use App\Services\VoucherService;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\LqiResource;


class LqiController extends Controller
{
    public function __construct(
        private readonly LqiBuilderService $builder,
        private readonly VoucherService $voucherService,
    ) {}

    /**
     * POST /contracts/{contract}/lqi/sync
     * Crea/actualiza el LQI draft para (period, currency).
     */
    public function sync(LqiSyncRequest $request, Contract $contract): JsonResponse
    {
        \Log::info('- SYNC  -------------------------------');
        $voucher = $this->builder->sync(
            $contract,
            $request->string('period'),
            strtoupper($request->string('currency'))
        );

        $voucher->load(['items.taxRate', 'booklet.voucherType']);
        return response()->json(new LqiResource($voucher), 200);
    }



    /**
     * POST /contracts/{contract}/lqi/issue
     * Emite el LQI draft (si existe y es consistente).
     */
    public function issue(LqiIssueRequest $request, Contract $contract): JsonResponse
    {
        $period   = $request->string('period');
        $currency = strtoupper($request->string('currency'));

        // Encontrar LQI draft único para (contract, period, currency)
        $voucherTypeId = VoucherType::query()->where('short_name', 'LQI')->value('id');
        $voucher = Voucher::query()
            ->where('voucher_type_id', $voucherTypeId)
            ->where('contract_id', $contract->id)
            ->whereDate('period', $period.'-01')
            ->where('currency', $currency)
            ->where('status', 'draft')
            ->firstOrFail();

        if ($request->filled('issue_date')) {
            $voucher->issue_date = $request->date('issue_date');
        }

        // Emitir (marca cargos como asentados y crea movimiento en CC)
        $issued = $this->voucherService->issue($voucher);
        $issued->load(['items.taxRate', 'booklet.voucherType']);
        return response()->json(new LqiResource($issued), 200);
    }

    /**
     * POST /contracts/{contract}/lqi/reopen
     * Reabre LQI emitida (si no tiene recibos aplicados), limpia asentados y vuelve a draft.
     */
    public function reopen(LqiReopenRequest $request, Contract $contract): JsonResponse
    {
        $period   = $request->string('period');
        $currency = strtoupper($request->string('currency'));

        $voucherTypeId = \App\Models\VoucherType::query()->where('short_name', 'LQI')->value('id');
        $voucher = Voucher::query()
            ->where('voucher_type_id', $voucherTypeId)
            ->where('contract_id', $contract->id)
            ->whereDate('period', $period.'-01')
            ->where('currency', $currency)
            ->where('status', 'issued')
            ->firstOrFail();

        $draft = $this->voucherService->reopenLqi($voucher, (string) $request->input('reason'));
        $draft->load(['items.taxRate', 'booklet.voucherType']);
        return response()->json(new LqiResource($draft), 200);
    }
}
