<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVoucherRequest;
use App\Http\Requests\UpdateVoucherRequest;
use App\Http\Resources\VoucherResource;
use App\Models\Voucher;
use App\Models\Contract;
use App\Models\Client;
use App\Models\Booklet;
use App\Models\VoucherItem;
use App\Models\VoucherPayment;
use App\Models\VoucherType;
use App\Models\VoucherAssociation;
use App\Models\VoucherApplication;
use App\Models\AccountMovement;
use App\Models\CashMovement;
use App\Services\VoucherCalculationService;
use App\Services\VoucherService;
use App\Services\VoucherGenerationService;
use App\Services\VoucherPreviewService;
use App\Enums\ContractClientRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class VoucherController extends Controller
{
    public function __construct(
        protected VoucherCalculationService $calculationService,
        protected VoucherService $voucherService,
    ) {}

    public function pendingCharges(Request $request, Contract $contract)
    {
        $period = Carbon::createFromFormat('Y-m', $request->get('period'))->startOfMonth();
        $currency = $contract->currency;
        $result = [];

        $voucherTypeAlq = VoucherType::where('short_name', 'ALQ')->first();
        $voucherTypeFac = VoucherType::where('short_name', 'FAC')->where('letter', 'X')->first();

        // 1. ALQ no generado
        $alq = $contract->vouchers()
            ->where('voucher_type_id', $voucherTypeAlq->id)
            ->where('period', $period)
            ->where('currency', $currency)
            ->where('status', '!=', 'cancelled')
            ->first();

            $alq =null;
        if (! $alq) {
            $period_amount = $contract->calculateRentForPeriod($period);
            $result[] = [
                'id' => 'rent-' . $period->format('Y-m'),
                'type' => 'rent',
                'description' => 'Alquiler ' . $period->translatedFormat('F Y'),
                'period' => $period->format('Y-m'),
                'suggested_amount' => $period_amount['amount'],
                'final_amount' => $period_amount['amount'],
                'currency' => $currency,
                'locked' => false,
                'voucher_full_number' => null,
                'voucher_status' => null,
            ];
        }

        // 2. Expenses sin voucher
        $expenses = $contract->expenses()
            ->where('included_in_collection', true)
            ->whereNull('voucher_id')
            ->get();

        foreach ($expenses as $expense) {
            $result[] = [
                'id' => 'expense-' . $expense->id,
                'type' => 'expense',
                'description' => $expense->description,
                'period' => optional($expense->period)->format('Y-m') ?? '',
                'suggested_amount' => $expense->amount,
                'final_amount' => $expense->amount,
                'currency' => $expense->currency,
                'locked' => false,
                'voucher_full_number' => null,
                'voucher_status' => null,
            ];
        }

        // 3. Punitorio si ALQ emitido y vencido
        if ($alq && $alq->status === 'issued' && now()->greaterThan(Carbon::parse($alq->due_date))) {
            $daysLate = now()->diffInDays(Carbon::parse($alq->due_date));
            $amount = 1;//$contract->calculatePenalty($alq, $daysLate);

            $result[] = [
                'id' => 'penalty-' . $alq->id,
                'type' => 'penalty',
                'description' => 'Punitorio mora ' . $period->translatedFormat('F Y'),
                'period' => $period->format('Y-m'),
                'suggested_amount' => $amount,
                'final_amount' => $amount,
                'currency' => $alq->currency,
                'locked' => false,
                'voucher_full_number' => null,
                'voucher_status' => null,
            ];
        }

        // 3b. Vouchers en estado draft (ALQ o FAC X)
        $vouchersDraft = $contract->vouchers()
        ->whereIn('voucher_type_id', [$voucherTypeAlq->id, $voucherTypeFac->id])
        ->where('period', $period)
        ->where('currency', $currency)
        ->where('status', 'draft')
        ->get();

        foreach ($vouchersDraft as $voucher) {
        foreach ($voucher->items as $item) {
            $result[] = [
                'id' => 'voucheritem-' . $item->id,
                'type' => $item->type,
                'description' => $item->description,
                'period' => $voucher->period,
                'suggested_amount' => $item->subtotal_with_vat ?? $item->unit_price,
                'final_amount' => $item->subtotal_with_vat ?? $item->unit_price,
                'currency' => $voucher->currency,
                'locked' => false,
                'voucher_id' => $voucher->id,
                'voucher_full_number' => $voucher->full_number,
                'voucher_status' => $voucher->status,
            ];
        }
        }

        // 4. Vouchers emitidos sin cobrar (ALQ / FAC X)
        $vouchers = $contract->vouchers()
            ->whereIn('voucher_type_id', [$voucherTypeAlq->id, $voucherTypeFac->id])
            ->where('period', $period)
            ->where('currency', $currency)
            ->where('status', 'issued')
            ->get();

        foreach ($vouchers as $voucher) {
            $applied = $voucher->applicationsReceived()->sum('amount');
            $pending = $voucher->total - $applied;

            if ($pending > 0) {
                $result[] = [
                    'id' => 'voucher-' . $voucher->id,
                    'type' => strtolower($voucher->voucher_type_short_name),
                    'description' => $voucher->items->pluck('description')->implode(', '),
                    'period' => $voucher->period,
                    'suggested_amount' => $pending,
                    'final_amount' => $pending,
                    'currency' => $voucher->currency,
                    'locked' => true,
                    'voucher_id' => $voucher->id,
                    'voucher_full_number' => $voucher->full_number,
                    'voucher_status' => $voucher->status,
                    'applied' => $applied,
                    'pending' => $pending,
                ];
            }
        }

        return response()->json($result);
    }


    public function generateVouchers(Request $request, Contract $contract)
    {
        $validated = $request->validate([
            'period' => ['required', 'date_format:Y-m'],
            'items' => ['required', 'array'],
            'items.*.type' => ['required', 'in:rent,expense,penalty'],
            'items.*.id' => ['required'],
            'items.*.amount' => ['required', 'numeric', 'min:0'],
        ]);

        $period = Carbon::createFromFormat('Y-m', $validated['period'])->startOfMonth();
        $items = collect($validated['items']);
        $currency = $contract->currency;

        $voucherTypeALQ = VoucherType::where('short_name', 'ALQ')->firstOrFail();
        $voucherTypeFAC = VoucherType::where('short_name', 'FAC')->where('letter', 'X')->firstOrFail();

        $alq = $contract->vouchers()
            ->where('voucher_type_id', $voucherTypeALQ->id)
            ->where('period', $period)
            ->where('currency', $currency)
            ->where('status', '!=', 'cancelled')
            ->first();

        $alqDraft = $alq && $alq->status === 'draft';
        $alqIssued = $alq && $alq->status === 'issued';

        $bookletALQ = Booklet::where('voucher_type_id', $voucherTypeALQ->id)->firstOrFail();
        $bookletFAC = Booklet::where('voucher_type_id', $voucherTypeFAC->id)->firstOrFail();

        $voucherAlq = $alqDraft ? $alq : null;
        $voucherFac = null;

        DB::beginTransaction();

        // Crear o agregar al ALQ
        if (! $alq && $items->where('type', '!=', 'penalty')->isNotEmpty()) {
            $voucherAlq = new Voucher([
                'booklet_id' => $bookletALQ->id,
                'voucher_type_id' => $voucherTypeALQ->id,
                'voucher_type_short_name' => 'ALQ',
                'voucher_type_letter' => 'X',
                'sale_point_number' => $bookletALQ->salePoint->number,
                'number' => null,
                'status' => 'draft',
                'period' => $period->format('Y-m'),
                'currency' => $currency,
                'issue_date' => now(),
                'due_date' => $contract->getDueDateForPeriod($period),
                'contract_id' => $contract->id,
                'client_id' => $contract->mainTenant()?->id,
            ]);
            $voucherAlq->save();
        }

        // Agregar ítems al ALQ
        if ($voucherAlq) {
            foreach ($items->whereIn('type', ['rent', 'expense']) as $item) {
                $voucherAlq->items()->create([
                    'type' => $item['type'],
                    'description' => $item['type'] === 'expense-rent'
                        ? 'Alquiler ' . $period->translatedFormat('F Y')
                        : 'Gasto asociado',
                    'quantity' => 1,
                    'unit_price' => $item['amount'],
                    'meta' => ['source' => 'manual'],
                ]);
            }
        }

        // Crear FAC X si ALQ ya emitido y hay conceptos adicionales
        if ($alqIssued && $items->whereIn('type', ['expense', 'penalty'])->isNotEmpty()) {
            $voucherFac = new Voucher([
                'booklet_id' => $bookletFAC->id,
                'voucher_type_id' => $voucherTypeFAC->id,
                'voucher_type_short_name' => 'FAC',
                'voucher_type_letter' => 'X',
                'sale_point_number' => $bookletFAC->salePoint->number,
                'number' => null,
                'status' => 'draft',
                'period' => $period->format('Y-m'),
                'currency' => $currency,
                'issue_date' => now(),
                'due_date' => now()->addDays(5),
                'contract_id' => $contract->id,
                'client_id' => $contract->mainTenant()?->id,
            ]);
            $voucherFac->save();

            foreach ($items->whereIn('type', ['expense', 'penalty']) as $item) {
                $voucherFac->items()->create([
                    'type' => $item['type'],
                    'description' => $item['type'] === 'penalty'
                        ? 'Punitorio por mora ' . $period->translatedFormat('F Y')
                        : 'Gasto asociado',
                    'quantity' => 1,
                    'unit_price' => $item['amount'],
                    'meta' => ['source' => 'manual'],
                ]);
            }
        }

        DB::commit();

        return response()->json([
            'alq_id' => $voucherAlq?->id,
            'fac_x_id' => $voucherFac?->id,
        ]);
    }


    public function applicable(Request $request)
    {
        $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
            'currency' => 'required|string|size:3',
            'voucher_type_short_name' => 'nullable|string', // Tipo de voucher destino
        ]);

        $clientId = $request->client_id;
        $currency = $request->currency;
        $voucherTypeShortName = $request->voucher_type_short_name;



        $vouchers = Voucher::where('client_id', $clientId)
            ->where('currency', $currency)
            ->where('status', 'issued')
            ->whereHas('voucherType', fn ($q) => $q->where('affects_account', true))
            ->with('voucherType')
            ->withSum('applications as total_applied', 'amount')
            ->withSum('applicationsReceived as total_applied_to', 'amount')
            ->get()
            ->filter(function ($voucher) use ($voucherTypeShortName) {
                $appliedTo = $voucher->total_applied_to ?? 0; // Aplicaciones HACIA este voucher
                $balance = $voucher->voucherType->credit
                    ? $voucher->total + $appliedTo
                    : $voucher->total - $appliedTo;

                // Solo incluir si tiene saldo pendiente
                if (round($balance, 2) <= 0) {
                    return false;
                }

                // Filtrar según el tipo de voucher destino
                if ($voucherTypeShortName) {
                    switch ($voucherTypeShortName) {
                        case 'N/C': // Nota de Crédito
                            // Solo comprobantes de débito (facturas, recibos, etc.)
                            return !$voucher->voucherType->credit;

                        case 'RCB': // Recibo
                        case 'RPG': // Recibo
                            // Comprobantes de débito (facturas, notas de débito) y crédito (notas de crédito)
                            // Se usan para imputar tanto facturas como NC que las cancelan
                            return !in_array($voucher->voucherType->short_name, ['RCB', 'RPG']);

                        default:
                            // Para otros tipos, mostrar todos los comprobantes aplicables
                            return true;
                    }
                }

                return true;
            })
            ->map(function ($voucher) {
                $appliedTo = $voucher->total_applied_to ?? 0; // Aplicaciones HACIA este voucher
                $pending = $voucher->total - $appliedTo;



                return [
                    'id' => $voucher->id,
                    'full_number' => $voucher->full_number,
                    'date' => $voucher->issue_date,
                    'type' => $voucher->voucherType->short_name,
                    'total' => $voucher->total,
                    'applied' => $appliedTo,
                    'pending' => round($pending, 2),
                ];
            })
            ->values();



        return response()->json($vouchers);
    }

    /**
     * Display a listing of vouchers
     */
    public function index(Request $request): JsonResponse
    {
        $query = Voucher::with(['client', 'contract', 'booklet.voucherType', 'items.taxRate']);

        // throw ValidationException::withMessages([
        //     'client_id' => $request->has('type'),
        // ]);
        // Filtrar por tipo de voucher
        if ($request->has('voucher_type')) {
            $query->whereHas('booklet.voucherType', function ($q) use ($request) {
                $q->where('short_name', $request->voucher_type);
            });
        }
        if ($request->has('booklet_id')) {
            $query->where('booklet_id', $request->booklet_id);
        }
        // Filtrar por status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('currency')) {
            $query->where('currency', strtoupper($request->currency));
        }

        // Filtrar por cliente
        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Filtrar por contrato
        if ($request->has('contract_id')) {
            $query->where('contract_id', $request->contract_id);
        }

        // Filtrar por período
        if ($request->has('period')) {
            $query->where('period', $request->period);
        }

        // Ordenar
        $query->orderBy('issue_date', 'desc');

        $perPage = $request->get('per_page', 15);
        $vouchers = $query->paginate($perPage);

        return VoucherResource::collection($vouchers)->response();
    }

    /**
     * Display the specified voucher
     */
    public function show(Voucher $voucher): JsonResponse
    {
        $voucher->load([
            'client',
            'contract',
            'booklet.voucherType',
            'items.taxRate',
            'payments.paymentMethod',
            'payments.cashAccount',
            'afipOperationType',
            'applications.appliedTo.voucherType',
            'applications.appliedTo.applicationsReceived',
            'associations.associatedVoucher',
        ]);
        return (new VoucherResource($voucher))->response();
    }

    /**
     * Store a newly created voucher
     */
    public function store(StoreVoucherRequest $request): JsonResponse
    {
        $voucher = $this->voucherService->createFromArray($request->validated());

        $voucher->load([
            'client',
            'contract',
            'booklet.voucherType',
            'items',
            'payments',
            'applications',
            'voucherAssociations',
            'afipOperationType'
        ]);

        return (new VoucherResource($voucher))->response()->setStatusCode(201);
    }


    public function update(UpdateVoucherRequest $request, Voucher $voucher): JsonResponse
    {
        if ($voucher->generated_from_collection) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'voucher' => 'Este voucher fue generado desde el Editor de Cobranzas y solo puede editarse desde allí.',
            ]);
        }

        $voucher = $this->voucherService->updateFromArray($voucher, $request->validated());

        $voucher->load([
            'client',
            'contract',
            'booklet.voucherType',
            'items',
            'payments.paymentMethod',
            'payments.cashAccount',
            'associations.associatedVoucher',
            'applications.appliedTo',
            'afipOperationType',
        ]);
        // return response()->json('in debug', 500);
        return (new VoucherResource($voucher))->response();
    }



    /**
     * Remove the specified voucher
     */
    public function destroy(Voucher $voucher): JsonResponse
    {
        $voucher->delete();
        return response()->json(['message' => 'Voucher eliminado correctamente']);
    }

    public function generateRentSummary(Request $request, Contract $contract)
    {

        $validated = $request->validate([
            'period' => ['required', 'date_format:Y-m'],
        ]);

        $voucher = app(VoucherGenerationService::class)
            ->generateRentSummary($contract, $validated['period']);

        return response()->json($voucher->load('items'), 201);
    }


    /**
     * Mark voucher as paid
     */
    public function markAsPaid(Request $request, Voucher $voucher): JsonResponse
    {
        $request->validate([
            'payment_date' => 'required|date',
            'paid_by_user_id' => 'nullable|exists:users,id'
        ]);

        $voucher->update([
            'status' => 'issued',
            'meta' => array_merge($voucher->meta ?? [], [
                'paid_at' => $request->payment_date,
                'paid_by_user_id' => $request->paid_by_user_id
            ])
        ]);

        return (new VoucherResource($voucher))->response();
    }

    /**
     * Issue voucher (change status from draft to issued)
     */
    public function issue(Request $request, Voucher $voucher): JsonResponse
    {
        if ($voucher->status !== 'draft') {
            return response()->json([
                'message' => 'Solo se pueden emitir vouchers en estado draft.'
            ], 422);
        }

        $voucher = $this->voucherService->issue($voucher);

        $voucher->load(['client', 'contract', 'booklet.voucherType', 'items']);
        return (new VoucherResource($voucher))->response();
    }

    public function collectionsIndex(Contract $contract, Request $request): JsonResponse
    {
        $request->validate([
            'period' => ['required', 'date_format:Y-m'],
            'per_page' => ['integer', 'min:1'],
            'page' => ['integer', 'min:1'],
            'sort_by' => ['nullable', 'string'],
            'sort_direction' => ['nullable', 'in:asc,desc'],
        ]);

        $period = $request->input('period');
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'issue_date');
        $sortDir = $request->input('sort_direction', 'desc');

        $vouchers = $contract->vouchers()
            // ->where('period', $period)
            ->whereHas('voucherType', fn ($q) => $q->where('short_name', 'COB'))
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);

        return response()->json($vouchers);
    }

    /**
     * Cancel voucher
     */
    public function cancel(Voucher $voucher): JsonResponse
    {
        if ($voucher->status === 'canceled') {
            return response()->json(['message' => 'El voucher ya está cancelado']);
        }

        $voucher->update(['status' => 'canceled']);

        // Si es un voucher de tipo COB, manejar gastos incluidos
        if ($voucher->booklet->voucherType->code === 'COB') {
            $this->handleCanceledCollectionVoucher($voucher);
        }

        return (new VoucherResource($voucher))->response();
    }

    // GET /collections/{voucher}/print
    // Devuelve PDF o HTML imprimible del comprobante de cobranza
    public function print(Voucher $voucher)
    {
        // TODO: Fix PDF import
        // return \Barryvdh\DomPDF\Facade\Pdf::loadView('vouchers.print', compact('voucher'))->stream();
        return response()->json(['message' => 'PDF generation temporarily disabled']);
    }

    // GET /contracts/{contract}/collections/preview?period=2025-08
    // Retorna JSON con los ítems a cobrar ese mes (alquiler, gastos, punitorios)
    public function preview(Request $request, Contract $contract)
    {
        $period = $request->input('period'); // formato YYYY-MM
        $items = app(VoucherPreviewService::class)->getPreviewFor($contract, $period);
        return response()->json($items);
    }

    // POST /contracts/{contract}/collections/generate
    // Genera el voucher tipo COB con todos los ítems del período
    public function generate(Request $request, Contract $contract)
    {
        $period = $request->input('period');
        $voucher = app(VoucherGenerationService::class)->generateForMonth($contract, $period);
        return response()->json($voucher, 201);
    }



        /**
     * Generate collections for a period
     */
    public function generateCollections(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'required|date_format:Y-m',
            'contract_id' => 'required|exists:contracts,id'
        ]);

        $contract = Contract::findOrFail($request->contract_id);
        $service = new VoucherGenerationService();
        $period = $request->period;

        $generated = $service->generateForMonth($contract, $period);

        return response()->json([
            'message' => 'Vouchers de cobranza generados correctamente',
            'generated_count' => count($generated),
            'generated' => VoucherResource::collection($generated)
        ]);
    }


    /**
     * Preview collections for a period
     */
    public function previewCollections(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'required|date_format:Y-m'
        ]);

        // TODO: Implement previewForMonth method in VoucherGenerationService
        return response()->json([
            'preview' => []
        ]);
    }

    /**
     * Handle canceled collection voucher
     */
    private function handleCanceledCollectionVoucher(Voucher $voucher): void
    {
        $voucher->items()
            ->where('type', 'service')
            ->get()
            ->each(function ($item) {
                $expenseId = $item->meta['expense_id'] ?? null;
                if ($expenseId) {
                    $expense = \App\Models\ContractExpense::find($expenseId);
                    if ($expense) {
                        $expense->included_in_collection = false;
                        $expense->save();
                    }
                }
            });
    }
}
