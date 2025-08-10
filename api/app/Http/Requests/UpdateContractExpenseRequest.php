<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\ContractExpense;
use App\Enums\ContractExpensePaidBy;
use App\Enums\ContractExpenseResponsibleParty;
use App\Enums\ContractExpenseStatus;

class UpdateContractExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var ContractExpense|null $expense */
        $expense = $this->route('contractExpense') ?? $this->route('contract_expense');

        if (!$expense) {
            throw new \RuntimeException('No se pudo obtener el gasto de contrato desde la ruta.');
        }

        // 🔒 Si está bloqueado (billed/credited/liquidated) no se puede editar nada
        if ($expense->is_locked) {
            return [
                '__locked' => ['prohibited'],
            ];
        }

        // Reglas base (si está pending o validated)
        $rules = [
            'service_type_id' => ['required', 'exists:service_types,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3'],
            'effective_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:effective_date'],
            'paid_by' => ['required', Rule::in(array_column(ContractExpensePaidBy::cases(), 'value'))],
            'responsible_party' => ['required', Rule::in(array_column(ContractExpenseResponsibleParty::cases(), 'value'))],
            'description' => ['nullable', 'string', 'max:500'],
        ];

        if ($this->contractExpense->status === ContractExpenseStatus::VALIDATED) {
            $rules['paid_by'] = ['sometimes', Rule::in(array_column(ContractExpensePaidBy::cases(), 'value'))];
            $rules['responsible_party'] = ['sometimes', Rule::in(array_column(ContractExpenseResponsibleParty::cases(), 'value'))];
        }        

        return $rules;
    }

    public function messages(): array
    {
        return [
            '__locked.prohibited' => 'No se puede editar un gasto vinculado a un comprobante o liquidación.',
            'service_type_id.required' => 'El tipo de servicio es obligatorio.',
            'service_type_id.exists' => 'El tipo de servicio seleccionado no es válido.',
            'amount.required' => 'El monto es obligatorio.',
            'amount.numeric' => 'El monto debe ser un número.',
            'currency.required' => 'La moneda es obligatoria.',
            'effective_date.required' => 'La fecha efectiva es obligatoria.',
            'due_date.after_or_equal' => 'La fecha de vencimiento debe ser igual o posterior a la fecha efectiva.',
            'paid_by.required' => 'Debe especificar quién pagó el gasto.',
            'paid_by.in' => 'El campo "pagado por" no es válido.',
            'paid_by.prohibited' => 'No se puede modificar "pagado por" en un gasto validado.',
            'responsible_party.required' => 'Debe especificar quién es el responsable del gasto.',
            'responsible_party.in' => 'El campo "responsable" no es válido.',
            'responsible_party.prohibited' => 'No se puede modificar el responsable en un gasto validado.',
        ];
    }
}
