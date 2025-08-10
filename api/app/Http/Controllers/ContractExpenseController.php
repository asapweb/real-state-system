<?php

namespace App\Http\Controllers;

use App\Models\ContractExpense;
use App\Http\Requests\StoreContractExpenseRequest;
use App\Http\Requests\UpdateContractExpenseRequest;
use App\Http\Resources\ContractExpenseResource;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Enums\ContractExpenseStatus;
use App\Enums\ContractExpensePaidBy;
use App\Enums\ContractExpenseResponsibleParty;  
use Carbon\Carbon;
use App\Services\ContractExpenseStatusService;

class ContractExpenseController extends Controller
{
    public function __construct(
        protected ContractExpenseStatusService $statusService
    ) {}

    /**
     * Listado paginado de Contract Expenses.
     */
    public function index(Request $request)
    {
        $query = ContractExpense::query()
            ->with([
                'contract.clients.client', // para mostrar info del contrato e inquilino/propietario
                'serviceType',
                'voucher',
                'generatedCreditNote',
                'liquidationVoucher',
            ]);

        // 🔍 Búsqueda avanzada
        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->whereHas('contract', fn($c) =>
                    $c->where('id', $search)
                      ->orWhere('monthly_amount', 'like', "%{$search}%")
                )
                ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // 🔎 Filtros específicos
        if ($request->filled('contract_id')) {
            $query->where('contract_id', $request->input('contract_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('paid_by')) {
            $query->where('paid_by', $request->input('paid_by'));
        }

        if ($request->filled('responsible_party')) {
            $query->where('responsible_party', $request->input('responsible_party'));
        }

        if ($request->filled('service_type_id')) {
            $query->where('service_type_id', $request->input('service_type_id'));
        }

        if ($request->filled('effective_date_from')) {
            $query->whereDate('effective_date', '>=', $request->input('effective_date_from'));
        }

        if ($request->filled('effective_date_to')) {
            $query->whereDate('effective_date', '<=', $request->input('effective_date_to'));
        }

        // 🗂️ Ordenamiento
        $sortBy = $request->input('sort_by', 'effective_date');
        $sortDirection = $request->input('sort_direction', 'desc');

        if (!in_array($sortBy, ['effective_date', 'amount', 'status', 'paid_by', 'responsible_party'])) {
            $sortBy = 'effective_date';
        }

        $query->orderBy($sortBy, $sortDirection);

        // 📄 Paginación
        $perPage = $request->input('per_page', 15);

        $expenses = $query->paginate($perPage);

        return ContractExpenseResource::collection($expenses);
    }

    public function store(StoreContractExpenseRequest $request)
    {
        $expense = ContractExpense::create($request->validated());

        return new ContractExpenseResource(
            $expense->load([
                'contract.clients.client',
                'serviceType',
                'voucher',
                'generatedCreditNote',
                'liquidationVoucher',
            ])
        );
    }

    public function update(UpdateContractExpenseRequest $request, ContractExpense $contractExpense)
    {
        // Bloqueo de edición si el gasto está vinculado a vouchers o liquidación
        if ($contractExpense->is_locked) {
            return response()->json([
                'message' => 'No se puede editar un gasto vinculado a un comprobante o liquidación.',
            ], 422);
        }

        $contractExpense->update($request->validated());

        return new ContractExpenseResource(
            $contractExpense->load([
                'contract.clients.client',
                'serviceType',
                'voucher',
                'generatedCreditNote',
                'liquidationVoucher',
            ])
        );
    }

    public function destroy(ContractExpense $contractExpense)
    {
        $contractExpense->delete();
        return response()->json(['message' => 'Gasto eliminado correctamente']);
    }

    public function registerPayment(Request $request, ContractExpense $contractExpense)
    {
        // 🔒 Bloqueo si el gasto está vinculado a vouchers/liquidaciones
        if ($contractExpense->is_locked) {
            return response()->json([
                'message' => 'No se puede registrar el pago de un gasto vinculado a un comprobante o liquidación.',
            ], 422);
        }

        // ✅ Validar que tenga comprobante adjunto
        if ($contractExpense->attachments()->count() === 0) {
            throw ValidationException::withMessages([
                'attachments' => 'Debe adjuntar al menos un comprobante antes de registrar el pago.',
            ]);
        }

        // ✅ Validar datos del request
        $validated = $request->validate([
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
        ]);

        // ✅ Actualizar el gasto
        $contractExpense->update([
            'is_paid' => true,
            'paid_at' => Carbon::parse($validated['paid_at']),
            'status' => $contractExpense->paid_by === ContractExpensePaidBy::TENANT 
                        && $contractExpense->responsible_party === ContractExpenseResponsibleParty::TENANT
                            ? ContractExpenseStatus::VALIDATED
                            : $contractExpense->status,
        ]);

        return new ContractExpenseResource(
            $contractExpense->fresh()->load([
                'contract.clients.client',
                'serviceType',
                'voucher',
                'generatedCreditNote',
                'liquidationVoucher',
                'attachments'
            ])
        );
    }


    public function validateExpense(ContractExpense $contractExpense)
    {
        // ✅ Verificar que el gasto no esté bloqueado por vouchers/liquidaciones previas
        if ($contractExpense->is_locked) {
            return response()->json([
                'message' => 'No se puede validar un gasto que ya está vinculado a un comprobante o liquidación.',
            ], 422);
        }

        // ✅ Validar que sea un gasto tenant→tenant
        if (
            $contractExpense->paid_by !== ContractExpensePaidBy::TENANT ||
            $contractExpense->responsible_party !== ContractExpenseResponsibleParty::TENANT
        ) {
            throw ValidationException::withMessages([
                'expense' => 'Solo se pueden validar gastos pagados y responsables del inquilino (tenant → tenant).',
            ]);
        }

        // ✅ Validar que aún no esté validado o en otro estado
        if ($contractExpense->status !== ContractExpenseStatus::PENDING) {
            throw ValidationException::withMessages([
                'status' => 'El gasto no está en estado pendiente y no puede validarse.',
            ]);
        }

        // ✅ Validar que tenga pago registrado antes de validar
        if (!$contractExpense->is_paid || !$contractExpense->paid_at) {
            throw ValidationException::withMessages([
                'paid' => 'Debe registrar el pago del gasto antes de validarlo.',
            ]);
        }

        // 🔄 Cambiar estado a VALIDATED
        $contractExpense->update([
            'status' => ContractExpenseStatus::VALIDATED,
        ]);

        return new ContractExpenseResource(
            $contractExpense->fresh()->load([
                'contract.clients.client',
                'serviceType',
                'voucher',
                'generatedCreditNote',
                'liquidationVoucher',
            ])
        );
    }

    /**
     * Cambiar el estado de un gasto de contrato.
     */
    public function changeStatus(Request $request, ContractExpense $contractExpense)
    {
        // ✅ Validar request
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', array_column(ContractExpenseStatus::cases(), 'value'))],
        ]);

        // ✅ Convertir a enum
        $targetStatus = ContractExpenseStatus::from($validated['status']);

        // 🔒 Ejecutar servicio de transición
        $this->statusService->changeStatus($contractExpense, $targetStatus);

        return new ContractExpenseResource(
            $contractExpense->fresh()->load([
                'contract.clients.client',
                'serviceType',
                'voucher',
                'generatedCreditNote',
                'liquidationVoucher',
                'attachments'
            ])
        );
    }
}
