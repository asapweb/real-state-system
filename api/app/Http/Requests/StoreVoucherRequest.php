<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = $this->input('voucher_type_short_name') ?? 'DEFAULT';

         // Reglas base que siempre se aplican
         $baseRules = [
            'generated_from_collection' => ['nullable', 'boolean'],
            'voucher_type_short_name' => ['required', 'string'],
            'booklet_id' => ['required', 'exists:booklets,id'],
            'currency' => ['required', 'string', 'size:3'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'client_id' => ['required', 'exists:clients,id'],
            'client_name' => ['required', 'string'],
            'client_address' => ['nullable', 'string'],
            'client_document_type_name' => ['nullable', 'string'],
            'client_document_number' => ['nullable', 'string'],
            'client_tax_condition_name' => ['nullable', 'string'],
            'client_tax_id_number' => ['nullable', 'string'],
            'contract_id' => ['nullable', 'exists:contracts,id'],
            'period' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],

            'items' => ['nullable', 'array'],
            'items.*.type' => ['required', 'string'],
            'items.*.description' => ['required_with:items', 'string'],
            'items.*.quantity' => ['required_with:items', 'numeric'],
            'items.*.unit_price' => ['required_with:items', 'numeric'],
            'items.*.tax_rate_id' => ['nullable', 'exists:tax_rates,id'],
            'items.*.contract_charge_id' => ['nullable', 'exists:contract_charges,id'],
            'items.*.impact' => ['nullable', 'string'],
        ];

        $specificRules = match ($type) {
            'ALQ' => $this->rulesForCob(),
            'LIQ' => $this->rulesForLiq(),
            'RCB', 'RPG' => $this->rulesForRecibo(),
            'FAC', 'N/C', 'N/D' => $this->rulesForFiscal(),
            default => [],
        };

        return array_merge($baseRules, $specificRules);
    }

    protected function rulesForCob(): array
    {
        return [
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date'],
            'period' => ['required', 'date'],
            'contract_id' => ['required', 'exists:contracts,id'],
            'client_id' => ['required', 'exists:clients,id', function($attribute, $value, $fail) {
                if (!$this->input('contract_id')) {
                    return; // Si no hay contrato, no validamos
                }

                // Verificar si el cliente es inquilino del contrato en contract_clients
                $isTenant = \DB::table('contract_clients')
                    ->where('contract_id', $this->input('contract_id'))
                    ->where('client_id', $value)
                    ->where('role', \App\Enums\ContractClientRole::TENANT)
                    ->exists();

                if (!$isTenant) {
                    $fail('El cliente debe ser el inquilino del contrato asociado.');
                }
            }],
            'items' => ['required', 'array', 'min:1'],

            // Datos del cliente editables
        ];
    }

    protected function rulesForLiq(): array
    {
        return [
            'voucher_type_short_name' => ['in:LIQ'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
        ];
    }

    protected function rulesForRecibo(): array
    {
        $total = floatval($this->input('total', 0));

        return [
            'payments' => ['nullable', 'array'],

            'payments.*.payment_method_id' => ['required_with:payments.*', 'exists:payment_methods,id'],
            'payments.*.cash_account_id' => ['nullable', 'exists:cash_accounts,id'],
            'payments.*.amount' => ['required_with:payments.*', 'numeric'],
            'payments.*.reference' => ['nullable', 'string'],

            'items' => ['nullable', 'array'],
            'applications' => ['nullable', 'array'],
        ];
    }

    protected function rulesForFiscal(): array
    {
        $type = $this->input('voucher_type_short_name');
        $letter = $this->input('letter');

        $rules = [
            'due_date' => ['required', 'date'],
            'afip_operation_type_id' => ['nullable', 'exists:afip_operation_types,id'],
            'service_date_from' => ['nullable', 'date'],
            'service_date_to' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
        ];

        if (in_array($letter, ['A', 'B', 'C'])) {
            $rules['afip_operation_type_id'] = ['required', 'exists:afip_operation_types,id'];
            $rules['service_date_from'] = ['required', 'date'];
            $rules['service_date_to'] = ['required', 'date'];
        }

        // Validación condicional para tax_rate_id según la letra
        if (in_array($letter, ['A', 'B'])) {
            $rules['items.*.tax_rate_id'] = ['required', 'exists:tax_rates,id'];
        } else {
            $rules['items.*.tax_rate_id'] = ['nullable', 'exists:tax_rates,id'];
        }

        if (in_array($type, ['N/C', 'N/D'])) {
            // $rules['associated_voucher_ids'] = ['required', 'array', 'min:1'];
        } else {
            $rules['associated_voucher_ids'] = ['nullable'];
        }

        return $rules;
    }
}
