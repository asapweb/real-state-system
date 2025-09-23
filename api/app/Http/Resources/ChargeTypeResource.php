<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use BackedEnum;

class ChargeTypeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                     => $this->id,
            'code'                   => $this->code,
            'name'                   => $this->name,
            // Tolerar enum o string:
            'tenant_impact'          => $this->impactToString($this->tenant_impact),
            'owner_impact'           => $this->impactToString($this->owner_impact),

            'requires_service_period'=> (bool) $this->requires_service_period,
            'requires_service_type'  => (bool) $this->requires_service_type,
            'requires_counterparty'  => $this->requires_counterparty, // 'tenant'|'owner'|null
            'currency_policy'        => $this->currency_policy,
            'is_active'              => (bool) $this->is_active,
        ];
    }

    private function impactToString($impact): string
    {
        // Si viene como enum (BackedEnum), devolver su value; si viene como string, devolverlo tal cual.
        if ($impact instanceof BackedEnum) {
            return (string) $impact->value;
        }
        return (string) $impact;
    }
}
