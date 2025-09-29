<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use BackedEnum;

class ContractChargeResource extends JsonResource
{
    public function toArray($request)
    {
        $type = $this->whenLoaded('chargeType') ?: $this->chargeType;
        $counterparty = $this->whenLoaded('counterparty') ?: ($this->counterparty_contract_client_id ? $this->counterparty : null);

        // meta de impacto
        $tenantMeta = $type->includeAndSignForTenant(); // ['include'=>bool,'sign'=>-1|0|+1]
        $ownerMeta  = $type->includeAndSignForOwner();

        $incTenant  = $tenantMeta['include'];
        $signTenant = $tenantMeta['sign'];
        $incOwner   = $ownerMeta['include'];
        $signOwner  = $ownerMeta['sign'];

        $signedTenant = $incTenant ? $type->signedAmountForTenant((float)$this->amount) : 0.0;
        $signedOwner  = $incOwner  ? $type->signedAmountForOwner((float)$this->amount)  : 0.0;

        return [
            'id'                       => $this->id,
            'contract_id'              => $this->contract_id,
            'charge_type'              => new ChargeTypeResource($type),
            'service_type_id'          => $this->service_type_id,
            'counterparty'             => $counterparty ? new ContractClientResource($counterparty) : null,

            'amount'                   => (float) $this->amount,
            'currency'                 => $this->currency,

            'effective_date'           => optional($this->effective_date)->toDateString(),
            'due_date'                 => optional($this->due_date)->toDateString(),
            'service_period_start'     => optional($this->service_period_start)->toDateString(),
            'service_period_end'       => optional($this->service_period_end)->toDateString(),
            'invoice_date'             => optional($this->invoice_date)->toDateString(),

            // 🔹 nuevos campos de LIQ
            'tenant_liquidation_voucher_id' => $this->tenant_liquidation_voucher_id,
            'owner_liquidation_voucher_id'  => $this->owner_liquidation_voucher_id,
            'tenant_settled_at'             => optional($this->tenant_settled_at)->toDateTimeString(),
            'owner_settled_at'              => optional($this->owner_settled_at)->toDateTimeString(),

            'description'              => $this->description,
            // Relaciones
            'contract' => $this->whenLoaded('contract', function () {
                return [
                    'id'             => $this->contract->id,
                    'start_date'     => $this->contract->start_date,
                    'end_date'       => $this->contract->end_date,
                    'monthly_amount' => $this->contract->monthly_amount,
                    'currency'       => $this->contract->currency,
                    'clients'        => $this->contract->clients->map(function ($contractClient) {
                        return [
                            'id'    => $contractClient->id,
                            'role'  => $contractClient->role,
                            'client' => [
                                'id'           => $contractClient->client->id,
                                'name'         => $contractClient->client->name,
                                'last_name'    => $contractClient->client->last_name,
                                'company_name' => $contractClient->client->company_name,
                                'client_type'  => $contractClient->client->client_type,
                            ],
                        ];
                    }),
                    // 👇 main tenant directo y plano
                    'main_tenant'    => $this->contract->relationLoaded('mainTenant') && $this->contract->mainTenant
                    ? [
                        'contract_client_id' => $this->contract->mainTenant->id,
                        'role'               => $this->contract->mainTenant->role,
                        'client' => [
                            'id'           => $this->contract->mainTenant->client->id,
                            'name'         => $this->contract->mainTenant->client->name,
                            'last_name'    => $this->contract->mainTenant->client->last_name,
                            'company_name' => $this->contract->mainTenant->client->company_name,
                            'client_type'  => $this->contract->mainTenant->client->client_type,
                            'full_name'  => $this->contract->mainTenant->client->full_name,
                        ],
                    ]
                    : null,
                ];
            }),
            // Computados para UI
            'tenant' => [
                'impact'        => $this->impactToString($type->tenant_impact),
                'include'       => $incTenant,
                'sign'          => $signTenant,
                'signed_amount' => $signedTenant,
            ],
            'owner' => [
                'impact'        => $this->impactToString($type->owner_impact),
                'include'       => $incOwner,
                'sign'          => $signOwner,
                'signed_amount' => $signedOwner,
            ],
            'tenantLiquidationVoucher' => $this->whenLoaded('tenantLiquidationVoucher', function () {
                return [
                    'id'         => $this->tenantLiquidationVoucher->id,
                    'number'     => $this->tenantLiquidationVoucher->number,
                    'date'       => $this->tenantLiquidationVoucher->date,
                    'amount'     => $this->tenantLiquidationVoucher->amount,
                    'created_at' => optional($this->tenantLiquidationVoucher->created_at)->toDateTimeString(),
                    'updated_at' => optional($this->tenantLiquidationVoucher->updated_at)->toDateTimeString(),
                ];
            }),


            'created_at'               => optional($this->created_at)->toDateTimeString(),
            'updated_at'               => optional($this->updated_at)->toDateTimeString(),
        ];
    }

    private function impactToString($impact): string
    {
        if ($impact instanceof BackedEnum) return (string)$impact->value;
        return (string)$impact;
    }
}
