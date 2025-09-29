<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Obtener el tipo del voucher existente desde la ruta
        $voucher = $this->route('voucher');
        $type = $voucher ? $voucher->voucher_type_short_name : ($this->input('voucher_type_short_name') ?? 'DEFAULT');

        // Reglas base que siempre se aplican
        $baseRules = [
            'generated_from_collection' => ['nullable', 'boolean'],
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
            'items.*.impact' => ['nullable', 'in:add,subtract'],

            'payments' => ['nullable', 'array'],
            'payments.*.payment_method_id' => ['required_with:payments.*', 'exists:payment_methods,id'],
            'payments.*.cash_account_id' => ['nullable', 'exists:cash_accounts,id'],
            'payments.*.amount' => ['required_with:payments.*', 'numeric'],
            'payments.*.reference' => ['nullable', 'string'],
            'items.*.contract_charge_id' => ['nullable', 'exists:contract_charges,id'],
            'items.*.impact' => ['nullable', 'in:add,subtract'],
        ];

        $specificRules = match ($type) {
            'COB', 'ALQ' => $this->rulesForCob(),
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
                if (!$this->route('voucher')->contract_id) {
                    return; // Si no hay contrato, no validamos
                }

                // Verificar si el cliente es inquilino del contrato en contract_clients
                $isTenant = \DB::table('contract_clients')
                    ->where('contract_id', $this->input('contract_id'))
                    ->where('client_id', $value)
                    ->where('role', \App\Enums\ContractClientRole::TENANT)
                    ->exists();

                if (!$isTenant) {
                    $fail('El cliente debe ser el inquilino del contrato asociado');
                }
            }],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric'],
            'items.*.unit_price' => ['required', 'numeric'],
            'items.*.tax_rate_id' => ['nullable', 'exists:tax_rates,id'],
        ];
    }

    protected function rulesForLiq(): array
    {
        return [
            'issue_date' => ['required', 'date'],
            'client_id' => ['required', 'exists:clients,id'],
            'currency' => ['required', 'string', 'size:3'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.amount' => ['required', 'numeric'],
            'items.*.quantity' => ['prohibited'], // No permitido en LIQ
            'items.*.unit_price' => ['prohibited'], // No permitido en LIQ
            'items.*.tax_rate_id' => ['prohibited'], // No permitido en LIQ
            'client_name' => ['required', 'string'],
            'client_address' => ['nullable', 'string'],
            'client_document_type_name' => ['nullable', 'string'],
            'client_document_number' => ['nullable', 'string'],
            'client_tax_condition_name' => ['nullable', 'string'],
            'client_tax_id_number' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function rulesForRecibo(): array
    {
        $total = floatval($this->input('total', 0));
        \Log::info('rules rec');
        \Log::info('total', ['total', $total]);
        return [
            'issue_date' => ['required', 'date'],
            'client_id' => ['required', 'exists:clients,id'],
            'currency' => ['required', 'string', 'size:3'],
            'items' => ['nullable', 'array'],
            'applications' => ['nullable', 'array'],
            'applications.*.applied_to_id' => ['required', 'exists:vouchers,id'],
            'applications.*.amount' => ['required', 'numeric'],
            'client_name' => ['required', 'string'],
            'client_address' => ['nullable', 'string'],
            'client_document_type_name' => ['nullable', 'string'],
            'client_document_number' => ['nullable', 'string'],
            'client_tax_condition_name' => ['nullable', 'string'],
            'client_tax_id_number' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function rulesForFiscal(): array
    {
        $type = $this->input('voucher_type_short_name');
        $letter = $this->input('letter');

        $rules = [
            'due_date' => ['required', 'date'],
            'afip_operation_type_id' => ['required', 'exists:afip_operation_types,id'],
            'service_date_from' => ['required', 'date'],
            'service_date_to' => ['required', 'date'],
        ];

        $rules['items'] = ['required', 'array', 'min:1'];

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
