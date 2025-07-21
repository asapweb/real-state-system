<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\Booklet;
use App\Models\VoucherPayment;
use App\Http\Resources\VoucherResource;
use App\Http\Requests\StoreVoucherRequest;
use App\Http\Requests\UpdateVoucherRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\VoucherItem;
use App\Services\VoucherGenerationService;
use App\Services\VoucherCalculationService;
use App\Services\VoucherService;
use Illuminate\Validation\ValidationException;
use App\Enums\ContractClientRole;

class VoucherController extends Controller
{
    public function __construct(
        protected VoucherCalculationService $calculationService,
        protected VoucherService $voucherService,
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
        $voucher = DB::transaction(function () use ($request) {
        $booklet = Booklet::findOrFail($request->booklet_id);


        // Obtener cliente (con validación especial para COB)
        if ($request->voucher_type_short_name === 'COB') {
            $contract = \App\Models\Contract::with('clients')->findOrFail($request->contract_id);

            $isTenant = $contract->clients()
                ->where('role', ContractClientRole::TENANT)
                ->where('contract_clients.client_id', $request->client_id)
                ->exists();

            if (! $isTenant) {
                throw ValidationException::withMessages([
                    'client_id' => 'El cliente seleccionado no es inquilino del contrato indicado.',
                ]);
            }
        }
            $client = \App\Models\Client::with('documentType', 'taxCondition')->findOrFail($request->client_id);

            $data = $request->validated();
            $data['booklet_id'] = $booklet->id;
            $data['voucher_type_id'] = $booklet->voucher_type_id;
            $data['voucher_type_short_name'] = $booklet->voucherType->short_name;
            $data['voucher_type_letter'] = $booklet->voucherType->letter;
            $data['sale_point_number'] = $booklet->salePoint->number;
            $data['number'] = null; // Se asignará al emitir
            $data['status'] = 'draft';

            $data['service_date_from'] = $request->input('service_date_from');
            $data['service_date_to'] = $request->input('service_date_to');
            $data['afip_operation_type_id'] = $request->input('afip_operation_type_id');

            $voucher = new Voucher($data);

            // Set items (antes de calcular)
            if ($request->has('items')) {
                $items = [];
                foreach ($request->items as $itemData) {
                    $items[] = new VoucherItem($itemData);
                }
                $voucher->setRelation('items', collect($items));
            }

            // Set payments (antes de calcular si el tipo lo requiere)
            if ($request->has('payments')) {
                $payments = [];
                foreach ($request->payments as $paymentData) {
                    $payments[] = new VoucherPayment($paymentData);
                }
                $voucher->setRelation('payments', collect($payments));
            }

            // Cálculo de totales según tipo
            $this->calculationService->calculateVoucher($voucher);

            $voucher->fill([
                'total' => $voucher->total,
                'subtotal_exempt' => $voucher->subtotal_exempt,
                'subtotal_untaxed' => $voucher->subtotal_untaxed,
                'subtotal_taxed' => $voucher->subtotal_taxed,
                'subtotal_vat' => $voucher->subtotal_vat,
                'subtotal_other_taxes' => $voucher->subtotal_other_taxes,
            ]);

            $voucher->save();

            // Guardar items
            if ($voucher->relationLoaded('items')) {
                $voucher->items()->saveMany($voucher->items);
            }

            // Guardar payments
            if ($voucher->relationLoaded('payments')) {
                $voucher->payments()->saveMany($voucher->payments);
            }

            // Guardar applications (para RCB/RPG)
            if ($request->has('applications')) {
                foreach ($request->applications as $applicationData) {
                    $voucher->applications()->create([
                        'applied_to_id' => $applicationData['applied_to_id'],
                        'amount' => $applicationData['amount'],
                    ]);
                }
            }

            // Guardar voucher_associations (para NC/ND)
            if ($request->has('associated_voucher_ids')) {
                foreach ($request->associated_voucher_ids as $assocData) {
                    $voucher->voucherAssociations()->create([
                        'associated_voucher_id' => $assocData
                    ]);
                }
            }

            return $voucher;
        });

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
        // 1. Solo permitir edición si está en estado draft
        if ($voucher->status !== 'draft') {
            return response()->json([
                'message' => 'Solo se pueden editar comprobantes en estado borrador.'
            ], 422);
        }

        $voucher = DB::transaction(function () use ($request, $voucher) {
            // 2. Campos que sí pueden actualizarse (ignoramos los inmutables)
            $updatableFields = collect($request->validated())->only([
                'issue_date',
                'due_date',
                'period',
                'contract_id',
                'client_id',
                'client_name',
                'client_address',
                'client_document_type_name',
                'client_document_number',
                'client_tax_condition_name',
                'client_tax_id_number',
                'currency',
                'notes',
                'meta',
                'afip_operation_type_id',
                'service_date_from',
                'service_date_to',
            ])->toArray();

            $voucher->fill($updatableFields);
            $voucher->save();

            // 3. Actualizar ítems si se envían
            if ($request->has('items')) {
                $voucher->items()->delete();
                $items = collect($request->items)->map(function($item) {
                    // Agregar type por defecto si no está presente
                    if (!isset($item['type'])) {
                        $item['type'] = 'service';
                    }

                    // Calcular campos requeridos según el tipo de voucher
                    if (isset($item['amount'])) {
                        // Para LIQ: usar amount directamente
                        $subtotal = $item['amount'];
                        $vatAmount = 0;
                        $subtotalWithVat = $subtotal;

                        // Agregar campos por defecto para LIQ
                        $item['quantity'] = 1;
                        $item['unit_price'] = $subtotal;
                    } else {
                        // Para vouchers fiscales: calcular desde quantity y unit_price
                        $quantity = $item['quantity'] ?? 1;
                        $unitPrice = $item['unit_price'] ?? 0;
                        $subtotal = $quantity * $unitPrice;

                        // Calcular VAT si hay tax_rate_id
                        $vatAmount = 0;
                        if (isset($item['tax_rate_id']) && $item['tax_rate_id']) {
                            $taxRate = \App\Models\TaxRate::find($item['tax_rate_id']);
                            if ($taxRate) {
                                $vatAmount = $subtotal * ($taxRate->rate / 100);
                            }
                        }

                        $subtotalWithVat = $subtotal + $vatAmount;
                    }

                    // Agregar campos calculados
                    $item['subtotal'] = $subtotal;
                    $item['vat_amount'] = $vatAmount;
                    $item['subtotal_with_vat'] = $subtotalWithVat;

                    return new VoucherItem($item);
                });
                $voucher->items()->saveMany($items);
                $voucher->load('items');
            }

            // 4. Actualizar pagos si se envían
            if ($request->has('payments')) {
                $voucher->payments()->delete();
                $payments = collect($request->payments)->map(function($payment) {
                    // Agregar payment_method_id por defecto si no está presente
                    if (!isset($payment['payment_method_id'])) {
                        // Buscar un payment method por defecto o crear uno
                        $defaultPaymentMethod = \App\Models\PaymentMethod::where('is_default', true)->first();
                        if (!$defaultPaymentMethod) {
                            // Crear un payment method por defecto
                            $cashAccount = \App\Models\CashAccount::first();
                            if (!$cashAccount) {
                                $cashAccount = \App\Models\CashAccount::create([
                                    'name' => 'Caja Principal',
                                    'type' => 'cash',
                                    'currency' => 'ARS',
                                ]);
                            }

                            $defaultPaymentMethod = \App\Models\PaymentMethod::create([
                                'name' => 'Efectivo',
                                'is_default' => true,
                                'default_cash_account_id' => $cashAccount->id,
                            ]);
                        }
                        $payment['payment_method_id'] = $defaultPaymentMethod->id;
                    }
                    return new VoucherPayment($payment);
                });
                $voucher->payments()->saveMany($payments);
                $voucher->load('payments');
            }

            // 5. Actualizar asociaciones si se envían
            if ($request->has('associated_voucher_ids')) {
                $voucher->associations()->delete();
                $associations = collect($request->associated_voucher_ids)->map(function($associatedVoucherId) use ($voucher) {
                    return new \App\Models\VoucherAssociation([
                        'voucher_id' => $voucher->id,
                        'associated_voucher_id' => $associatedVoucherId,
                    ]);
                });
                $voucher->associations()->saveMany($associations);
                $voucher->load('associations');
            }

            // 6. Actualizar aplicaciones si se envían
            if ($request->has('applications')) {
                $voucher->applications()->delete();
                $applications = collect($request->applications)->map(function($application) use ($voucher) {
                    return new \App\Models\VoucherApplication([
                        'voucher_id' => $voucher->id,
                        'applied_to_id' => $application['applied_to_id'],
                        'amount' => $application['amount'],
                    ]);
                });
                $voucher->applications()->saveMany($applications);
                $voucher->load('applications');
            }

            // 7. Recalcular totales
            $this->calculationService->calculateVoucher($voucher);

            // 6. Aplicar los valores recalculados
            $voucher->fill([
                'total' => $voucher->total,
                'subtotal_exempt' => $voucher->subtotal_exempt,
                'subtotal_untaxed' => $voucher->subtotal_untaxed,
                'subtotal_taxed' => $voucher->subtotal_taxed,
                'subtotal_vat' => $voucher->subtotal_vat,
                'subtotal_other_taxes' => $voucher->subtotal_other_taxes,
            ]);

            $voucher->save();

            return $voucher;
        });

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

        /**
     * Generate collections for a period
     */
    public function generateCollections(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'required|date_format:Y-m'
        ]);

        $service = new VoucherGenerationService();
        $period = \Carbon\Carbon::createFromFormat('Y-m', $request->period);

        $generated = $service->generateForMonth($period);

        return response()->json([
            'message' => 'Vouchers de cobranza generados correctamente',
            'generated_count' => $generated->count(),
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

        $service = new VoucherGenerationService();
        $period = \Carbon\Carbon::createFromFormat('Y-m', $request->period);

        $preview = $service->previewForMonth($period);

        return response()->json([
            'preview' => $preview
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
