<?php

namespace App\Http\Controllers;

use App\Enums\VoucherStatus;
use App\Exceptions\VoucherCancellationConflictException;
use App\Http\Requests\StoreVoucherRequest;
use App\Http\Requests\UpdateVoucherRequest;
use App\Http\Resources\VoucherResource;
use App\Models\Voucher;
use App\Models\Contract;
use App\Services\VoucherCancellationService;
use App\Services\VoucherCalculationService;
use App\Services\VoucherService;
use App\Services\VoucherPreviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class VoucherController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected VoucherCalculationService $calculationService,
        protected VoucherService $voucherService,
        protected VoucherCancellationService $voucherCancellationService,
    ) {}

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
            ->where('status', VoucherStatus::Issued->value)
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




    /**
     * Issue voucher (change status from draft to issued)
     */
    public function issue(Request $request, Voucher $voucher): JsonResponse
    {
        if ($voucher->status !== VoucherStatus::Draft) {
            return response()->json([
                'message' => 'Solo se pueden emitir vouchers en estado draft.'
            ], 422);
        }

        $voucher = $this->voucherService->issue($voucher);

        $voucher->load(['client', 'contract', 'booklet.voucherType', 'items']);
        return (new VoucherResource($voucher))->response();
    }


    /**
     * Cancel voucher
     */
    public function cancel(Request $request, Voucher $voucher): JsonResponse
    {
        $this->authorize('cancel', $voucher);

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10'],
        ]);

        try {
            $result = $this->voucherCancellationService->cancel(
                $voucher,
                $data['reason'],
                $request->user()
            );
        } catch (VoucherCancellationConflictException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'reasons' => $exception->reasons(),
            ], 409);
        }

        $voucher->refresh();

        $status = $voucher->status instanceof VoucherStatus
            ? $voucher->status->value
            : $voucher->status;

        return response()->json([
            'data' => array_merge([
                'id' => $voucher->id,
                'status' => $status,
            ], $result),
        ]);
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
