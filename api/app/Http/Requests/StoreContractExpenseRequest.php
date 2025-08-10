<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\ContractExpensePaidBy;
use App\Enums\ContractExpenseResponsibleParty;

class StoreContractExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ajustar según política de permisos
    }

    public function rules(): array
    {
        return [
            'contract_id' => ['required', 'exists:contracts,id'],
            'service_type_id' => ['required', 'exists:service_types,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'effective_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'paid_by' => ['required', Rule::in(array_column(ContractExpensePaidBy::cases(), 'value'))],
            'responsible_party' => ['required', Rule::in(array_column(ContractExpenseResponsibleParty::cases(), 'value'))],
            'is_paid' => ['boolean'],
            'paid_at' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'included_in_voucher' => ['boolean'],
            'voucher_id' => ['nullable', 'exists:vouchers,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'contract_id' => 'contrato',
            'service_type_id' => 'tipo de servicio',
            'amount' => 'monto',
            'currency' => 'moneda',
            'effective_date' => 'fecha efectiva',
            'due_date' => 'fecha de vencimiento',
            'paid_by' => 'pagado por',
            'responsible_party' => 'responsable',
            'is_paid' => 'estado de pago',
            'paid_at' => 'fecha de pago',
            'description' => 'descripción',
            'included_in_voucher' => 'inclusión en comprobante',
            'voucher_id' => 'comprobante asociado',
        ];
    }
}
