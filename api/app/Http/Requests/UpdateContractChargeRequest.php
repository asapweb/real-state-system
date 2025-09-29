<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ChargeType;
use App\Models\ContractClient;
use App\Models\ContractCharge;
// use App\Models\Contract; // <- descomentar si validás currency del contrato

class UpdateContractChargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ajustá a tu política
    }

    /**
     * Normalizaciones:
     * - amount a positivo (si viene)
     * - currency en mayúsculas (si viene)
     */
    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('amount') && is_numeric($this->input('amount'))) {
            $data['amount'] = abs((float)$this->input('amount'));
        }

        if ($this->has('currency') && is_string($this->input('currency'))) {
            $data['currency'] = strtoupper($this->input('currency'));
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        $isPatch = $this->isMethod('PATCH');

        // Para PATCH usamos sometimes; para PUT, requerimos como en Store
        $req   = $isPatch ? ['sometimes'] : ['required'];
        $opt   = $isPatch ? ['sometimes','nullable'] : ['nullable'];

        return [
            'contract_id'                     => array_merge($req, ['integer','exists:contracts,id']),
            'charge_type_id'                  => array_merge($req, ['integer','exists:charge_types,id']),
            'service_type_id'                 => array_merge($opt, ['integer','exists:service_types,id']),
            'counterparty_contract_client_id' => array_merge($opt, ['integer','exists:contract_clients,id']),

            'amount'         => array_merge($req, ['numeric','min:0.01']),
            'currency'       => array_merge($req, ['string','size:3']),
            'effective_date' => array_merge($req, ['date']),
            'due_date'       => array_merge($opt, ['date','after_or_equal:effective_date']),

            'service_period_start' => array_merge($opt, ['date']),
            'service_period_end'   => array_merge($opt, ['date','after_or_equal:service_period_start']),
            'invoice_date'         => array_merge($opt, ['date']),

            'description' => array_merge($opt, ['string','max:2000']),
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            /** @var ContractCharge|null $existing */
            $existing = $this->route('contract_charge'); // si usás route-model binding
            if (!$existing instanceof ContractCharge) {
                // fallback por si el parámetro de ruta tiene otro nombre
                $existing = ContractCharge::find($this->route('id'));
            }

            // Resolver valores efectivos (input si viene, sino los actuales)
            $contractId  = $this->input('contract_id', $existing?->contract_id);
            $typeId      = $this->input('charge_type_id', $existing?->charge_type_id);
            $ccId        = $this->input('counterparty_contract_client_id', $existing?->counterparty_contract_client_id);
            $svcTypeId   = $this->input('service_type_id', $existing?->service_type_id);
            $spStart     = $this->input('service_period_start', optional($existing?->service_period_start)->toDateString());
            $spEnd       = $this->input('service_period_end', optional($existing?->service_period_end)->toDateString());
            $currency    = $this->input('currency', $existing?->currency);

            // Cargar tipo
            $type = $typeId ? ChargeType::find($typeId) : null;
            if (!$type) return;

            // 1) requires_counterparty: 'tenant' | 'owner' | null
            $reqCounterparty = $type->requires_counterparty;
            if ($reqCounterparty !== null) {
                if (!$ccId) {
                    $v->errors()->add('counterparty_contract_client_id', 'La contraparte es obligatoria para este tipo de cargo.');
                    // si falta, no seguimos chequeando pertenencia/rol
                } else {
                    $cc = ContractClient::query()
                        ->where('id', $ccId)
                        ->where('contract_id', $contractId)
                        ->first();

                    if (!$cc) {
                        $v->errors()->add('counterparty_contract_client_id', 'La contraparte debe pertenecer al mismo contrato.');
                    } else {
                        $role = strtolower($cc->role->value); // 'tenant'|'owner'
                        if ($reqCounterparty !== $role) {
                            $v->errors()->add('counterparty_contract_client_id', "La contraparte debe ser de tipo {$reqCounterparty}.");
                        }
                    }
                }
            }

            // 2) requires_service_period
            if ($type->requires_service_period) {
                if (empty($spStart) || empty($spEnd)) {
                    $v->errors()->add('service_period_start', 'El período de servicio es obligatorio para este tipo de cargo.');
                }
            }

            // 3) requires_service_type
            if (property_exists($type, 'requires_service_type') ? (bool)$type->requires_service_type : false) {
                if (!$svcTypeId) {
                    $v->errors()->add('service_type_id', 'El tipo de servicio es obligatorio para este tipo de cargo.');
                }
            }

            // 4) (Opcional) Política de moneda CONTRACT_CURRENCY
            /*
            if (($type->currency_policy ?? null) === 'CONTRACT_CURRENCY' && $contractId) {
                $contract = Contract::find($contractId);
                if ($contract && strtoupper((string)$currency) !== strtoupper((string)$contract->currency)) {
                    $v->errors()->add('currency', 'La moneda debe coincidir con la moneda del contrato para este tipo de cargo.');
                }
            }
            */
        });
    }
}
