<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ChargeType;
use App\Models\ContractClient;

class StoreContractChargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ajustá a tu política
    }

    public function rules(): array
    {
        return [
            'contract_id'                     => ['required','integer','exists:contracts,id'],
            'charge_type_id'                  => ['required','integer','exists:charge_types,id'],
            'service_type_id'                 => ['nullable','integer','exists:service_types,id'],
            'counterparty_contract_client_id' => ['nullable','integer','exists:contract_clients,id'],

            'amount'     => ['required','numeric','min:0.01'],
            'currency'   => ['required','string','size:3'],
            'effective_date' => ['required','date'],
            'due_date'   => ['nullable','date','after_or_equal:effective_date'],

            'service_period_start' => ['nullable','date'],
            'service_period_end'   => ['nullable','date','after_or_equal:service_period_start'],
            'invoice_date'         => ['nullable','date'],

            'description' => ['nullable','string','max:2000'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $type = ChargeType::find($this->input('charge_type_id'));
            if (!$type) return;

            // 1) requires_counterparty: 'tenant' | 'owner' | null
            $req = $type->requires_counterparty;
            $ccId = $this->input('counterparty_contract_client_id');
            if ($req !== null) {
                if (!$ccId) {
                    $v->errors()->add('counterparty_contract_client_id', 'Counterparty is required for this charge type.');
                    return;
                }
                $cc = ContractClient::query()
                    ->where('id', $ccId)
                    ->where('contract_id', $this->input('contract_id'))
                    ->first();
                if (!$cc) {
                    $v->errors()->add('counterparty_contract_client_id', 'Counterparty must belong to the same contract.');
                    return;
                }
                $role = strtolower($cc->role); // e.g. 'tenant' | 'owner'
                if ($req !== $role) {
                    $v->errors()->add('counterparty_contract_client_id', "Counterparty must be a {$req}.");
                }
            }

            // 2) requires_service_period
            if ($type->requires_service_period) {
                if (!$this->input('service_period_start') || !$this->input('service_period_end')) {
                    $v->errors()->add('service_period_start', 'Service period is required for this charge type.');
                }
            }

            // 3) currency policy (opcional: fuerza moneda de contrato)
            // Si usás CONTRACT_CURRENCY, podrías validar contra la moneda del contrato aquí.
        });
    }
}
