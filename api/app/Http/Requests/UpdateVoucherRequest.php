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
            'period' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];

        $specificRules = match ($type) {
            'COB' => $this->rulesForCob(),
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
            'period' => ['required', 'string'],
            'contract_id' => ['required', 'exists:contracts,id'],
            'client_id' => ['required', 'exists:clients,id', function($attribute, $value, $fail) {
                if (!$this->route('voucher')->contract_id) {
                    return; // Si no hay contrato, no validamos
                }

                // Verificar si el cliente es inquilino del contrato en contract_clients
                $isTenant = \DB::table('contract_clients')
                    ->where('contract_id', $this->route('voucher')->contract_id)
                    ->where('client_id', $value)
                    ->where('role', \App\Enums\ContractClientRole::TENANT)
                    ->exists();

                if (!$isTenant) {
                    $fail('El cliente debe ser el inquilino del contrato asociado.');
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

        return [
            'issue_date' => ['required', 'date'],
            'client_id' => ['required', 'exists:clients,id'],
            'currency' => ['required', 'string', 'size:3'],
            'payments' => $total > 0
                ? ['required', 'array', 'min:1']
                : ['nullable', 'array'],
            'payments.*.payment_method_id' => ['required_with:payments.*', 'exists:payment_methods,id'],
            'payments.*.cash_account_id' => ['nullable', 'exists:cash_accounts,id'],
            'payments.*.amount' => ['required_with:payments.*', 'numeric'],
            'payments.*.reference' => ['nullable', 'string'],
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

        $rules = [
            'due_date' => ['required', 'date'],
            'afip_operation_type_id' => ['required', 'exists:afip_operation_types,id'],
            'service_date_from' => ['required', 'date'],
            'service_date_to' => ['required', 'date'],
        ];

        if (in_array($type, ['FAC', 'N/D'])) {
            $rules['items'] = ['required', 'array', 'min:1'];
            $rules['items.*.description'] = ['required', 'string'];
            $rules['items.*.quantity'] = ['required', 'numeric'];
            $rules['items.*.unit_price'] = ['required', 'numeric'];
            $rules['items.*.tax_rate_id'] = ['nullable', 'exists:tax_rates,id'];
        }

        if ($type === 'N/C') {
            $rules['items'] = ['nullable', 'array'];
            $rules['items.*.description'] = ['required_with:items', 'string'];
            $rules['items.*.quantity'] = ['required_with:items', 'numeric'];
            $rules['items.*.unit_price'] = ['required_with:items', 'numeric'];
            $rules['items.*.tax_rate_id'] = ['nullable', 'exists:tax_rates,id'];

            $rules['applications'] = ['nullable', 'array'];
            $rules['applications.*.applied_to_id'] = ['required_with:applications', 'exists:vouchers,id'];
            $rules['applications.*.amount'] = ['required_with:applications', 'numeric', 'min:0.01'];
            $rules['applications.*.description'] = ['nullable', 'string'];
        }

        if ($type === 'N/D') {
            $rules['associated_voucher_ids'] = ['required', 'array', 'min:1'];
        } else {
            $rules['associated_voucher_ids'] = ['nullable'];
        }

        return $rules;
    }
}
