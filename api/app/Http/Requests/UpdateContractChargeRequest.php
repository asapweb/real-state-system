<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContractChargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_type_id'                 => ['nullable','integer','exists:service_types,id'],
            'counterparty_contract_client_id' => ['nullable','integer','exists:contract_clients,id'],

            'amount'     => ['sometimes','numeric','min:0.01'],
            'currency'   => ['sometimes','string','size:3'],
            'effective_date' => ['sometimes','date'],
            'due_date'   => ['nullable','date','after_or_equal:effective_date'],

            'service_period_start' => ['nullable','date'],
            'service_period_end'   => ['nullable','date','after_or_equal:service_period_start'],
            'invoice_date'         => ['nullable','date'],

            'description' => ['nullable','string','max:2000'],
        ];
    }
}
