<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ChargeType;
use App\Models\ContractClient;
// use App\Models\Contract; // <- si validás currency del contrato (ver abajo)

class StoreContractChargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ajustá a tu política
    }

    /**
     * Normalizaciones rápidas antes de validar:
     * - amount siempre positivo (el signo lo da el tipo)
     * - currency en mayúsculas
     */
    protected function prepareForValidation(): void
    {
        $amount = $this->input('amount');
        $currency = $this->input('currency');

        $this->merge([
            'amount'   => is_numeric($amount) ? abs((float)$amount) : $amount,
            'currency' => is_string($currency) ? strtoupper($currency) : $currency,
        ]);
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
                    $v->errors()->add('counterparty_contract_client_id', 'La contraparte es obligatoria para este tipo de cargo.');
                    return;
                }
                $cc = ContractClient::query()
                    ->where('id', $ccId)
                    ->where('contract_id', $this->input('contract_id'))
                    ->first();
                if (!$cc) {
                    $v->errors()->add('counterparty_contract_client_id', 'La contraparte debe pertenecer al mismo contrato.');
                    return;
                }
                $role = strtolower($cc->role->value); // 'tenant' | 'owner'
                if ($req !== $role) {
                    $v->errors()->add('counterparty_contract_client_id', "La contraparte debe ser de tipo {$req}.");
                }
            }

            // 2) requires_service_period
            if ($type->requires_service_period) {
                if (!$this->input('service_period_start') || !$this->input('service_period_end')) {
                    $v->errors()->add('service_period_start', 'El período de servicio es obligatorio para este tipo de cargo.');
                }
            }

            // 3) NEW: requires_service_type
            if (property_exists($type, 'requires_service_type') ? (bool)$type->requires_service_type : false) {
                if (!$this->input('service_type_id')) {
                    $v->errors()->add('service_type_id', 'El tipo de servicio es obligatorio para este tipo de cargo.');
                }
            }

            // 4) (Opcional) Política de moneda CONTRACT_CURRENCY
            // Si querés forzar la moneda del contrato cuando currency_policy = CONTRACT_CURRENCY:
            /*
            if (($type->currency_policy ?? null) === 'CONTRACT_CURRENCY') {
                $contract = Contract::find($this->input('contract_id'));
                if ($contract && strtoupper($this->input('currency')) !== strtoupper($contract->currency)) {
                    $v->errors()->add('currency', 'La moneda debe coincidir con la moneda del contrato para este tipo de cargo.');
                }
            }
            */
        });
    }
}
