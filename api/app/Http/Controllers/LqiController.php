<?php

namespace App\Http\Controllers;

use App\Http\Requests\LqiSyncRequest;
use App\Http\Requests\LqiIssueRequest;
use App\Http\Requests\LqiReopenRequest;
use App\Models\Contract;
use App\Models\Voucher;
use App\Models\VoucherType;
use App\Services\LqiBuilderService;
use App\Services\LqiOverviewService;
use App\Services\VoucherService;
use App\Services\LqiPostIssueService;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\LqiResource;
use Illuminate\Http\Request;
use Carbon\Carbon;


class LqiController extends Controller
{
    protected $builder;
    protected $overviewService;
    protected $voucherService;
    protected $postIssueService;

    public function __construct(
        LqiBuilderService $builder,
        LqiOverviewService $overviewService,
        VoucherService $voucherService,
        LqiPostIssueService $postIssueService
    ) {
        $this->builder = $builder;
        $this->overviewService = $overviewService;
        $this->voucherService = $voucherService;
        $this->postIssueService = $postIssueService;
    }

    public function postIssue(Request $request, Contract $contract, string $period, string $currency): JsonResponse
    {
        if (!config('features.lqi.post_issue')) {
            return response()->json([
                'message' => 'La emisión post-liquidación está deshabilitada.',
            ], 403);
        }

        $user = $request->user();
        if (!$user || !$user->can('lqi.post_issue_adjustments')) {
            return response()->json([
                'message' => 'No tenés permisos para emitir ajustes post-liquidación.',
            ], 403);
        }

        $result = $this->postIssueService->issueAdjustments($contract->id, $period, $currency);

        $statusCode = 200;
        if (in_array($result['result'] ?? null, ['blocked', 'invalid_status_for_post_issue'], true)) {
            $statusCode = 409;
        }

        return response()->json($result, $statusCode);
    }

    public function postIssueBulk(Request $request, string $period): JsonResponse
    {
        if (!config('features.lqi.post_issue')) {
            return response()->json([
                'message' => 'La emisión post-liquidación está deshabilitada.',
            ], 403);
        }

        $user = $request->user();
        if (!$user || !$user->can('lqi.post_issue_adjustments')) {
            return response()->json([
                'message' => 'No tenés permisos para emitir ajustes post-liquidación.',
            ], 403);
        }

        $validated = $request->validate([
            'currency' => ['nullable', 'string', 'max:10'],
            'contract_id' => ['nullable', 'integer'],
            'state' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'string', 'max:20'],
            'has_eligibles' => ['nullable', 'boolean'],
        ]);

        $result = $this->postIssueService->issueAdjustmentsBulk($period, $validated);

        return response()->json($result);
    }

    public function overview(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'period'     => ['required', 'date_format:Y-m'],
            'currency'   => ['nullable', 'string', 'max:10'],
            'contract_id'=> ['nullable', 'integer'],
            'status'     => ['nullable', 'string', 'max:20'],
            'page'       => ['nullable', 'integer', 'min:1'],
            'per_page'   => ['nullable', 'integer', 'min:1', 'max:200'],
            'has_eligibles' => ['nullable', 'boolean'],
        ]);

        $data = $this->overviewService->overview($filters);
        $data['features']['lqi']['post_issue'] = config('features.lqi.post_issue', false);
        return response()->json($data);
    }

    public function kpis(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'period'     => ['required', 'date_format:Y-m'],
            'currency'   => ['nullable', 'string', 'max:10'],
            'contract_id'=> ['nullable', 'integer'],
            'status'     => ['nullable', 'string', 'max:20'],
            'has_eligibles' => ['nullable', 'boolean'],
        ]);

        $data = $this->overviewService->kpis($filters);
        return response()->json($data);
    }

    public function generateBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period'     => ['required', 'date_format:Y-m'],
            'currency'   => ['nullable', 'string', 'max:10'],
            'contract_id'=> ['nullable', 'integer'],
            'status'     => ['nullable', 'string', 'max:20'],
            'has_eligibles' => ['nullable', 'boolean'],
            'allow_empty'   => ['nullable', 'boolean'],
        ]);

        $result = $this->overviewService->generate($validated);
        return response()->json($result, 200);
    }

    public function issueBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period'     => ['required', 'date_format:Y-m'],
            'currency'   => ['nullable', 'string', 'max:10'],
            'contract_id'=> ['nullable', 'integer'],
            'status'     => ['nullable', 'string', 'max:20'],
            'has_eligibles' => ['nullable', 'boolean'],
            'allow_empty'   => ['nullable', 'boolean'],
        ]);

        $result = $this->overviewService->issue($validated);
        return response()->json($result, 200);
    }

    public function reopenBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period'     => ['required', 'date_format:Y-m'],
            'currency'   => ['nullable', 'string', 'max:10'],
            'contract_id'=> ['nullable', 'integer'],
            'status'     => ['nullable', 'string', 'max:20'],
            'reason'     => ['nullable', 'string', 'max:255'],
            'has_eligibles' => ['nullable', 'boolean'],
            'allow_empty'   => ['nullable', 'boolean'],
        ]);

        $reason = $validated['reason'] ?? null;
        unset($validated['reason']);

        $result = $this->overviewService->reopen($validated, $reason);
        return response()->json($result, 200);
    }

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

        $periodCarbon = Carbon::createFromFormat('Y-m', $period)->startOfMonth();

        $overview = $this->overviewService->overview([
            'period' => $period,
            'currency' => $currency,
            'contract_id' => $contract->id,
            'per_page' => 25,
        ]);

        $row = collect($overview['data'] ?? [])->first();
        if (!$row) {
            return response()->json([
                'message' => 'No se encontraron cargos para el período indicado.',
            ], 422);
        }

        $blocked = $row['blocked_reasons'] ?? [];
        if (!empty($blocked)) {
            $message = 'La liquidación está bloqueada para este período.';
            switch ($blocked[0]) {
                case 'pending_adjustment':
                    $message = 'Ajuste pendiente del período — no se puede generar.';
                    break;
                case 'missing_rent':
                    $message = 'Falta la cuota RENT del período — no se puede generar.';
                    break;
            }

            return response()->json([
                'message' => $message,
            ], 422);
        }

        $hasAdd = ($row['add_count'] ?? 0) > 0;
        $hasSubtract = ($row['subtract_count'] ?? 0) > 0;

        if (!$hasAdd && !$hasSubtract) {
            return response()->json([
                'message' => 'Sin cargos del período.',
            ], 422);
        }

        if (!$hasAdd && $hasSubtract) {
            $creditIssued = $this->overviewService->issueCreditNote($contract, $periodCarbon, $currency, null);

            return response()->json([
                'credit_note_emitted' => $creditIssued,
            ], 200);
        }

        // Encontrar LQI draft único para (contract, period, currency)
        $voucherTypeId = VoucherType::query()->where('short_name', 'LQI')->value('id');
        $voucher = Voucher::query()
            ->where('voucher_type_id', $voucherTypeId)
            ->where('contract_id', $contract->id)
            ->whereDate('period', $periodCarbon->toDateString())
            ->where('currency', $currency)
            ->where('status', 'draft')
            ->firstOrFail();

        if ($request->filled('issue_date')) {
            $voucher->issue_date = $request->date('issue_date');
        }

        // Emitir LQI y, si corresponde, NC asociada
        $issued = $this->voucherService->issue($voucher);
        $issued->load(['items.taxRate', 'booklet.voucherType']);

        if ($hasSubtract) {
            $this->overviewService->issueCreditNote($contract, $periodCarbon, $currency, $issued);
        }

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
