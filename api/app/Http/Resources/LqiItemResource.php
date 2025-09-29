<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LqiItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'type'                => $this->type,
            'description'         => $this->description,
            'quantity'            => (int) $this->quantity,
            'unit_price'          => (float) $this->unit_price,
            'subtotal'            => (float) $this->subtotal,
            'vat_amount'          => (float) $this->vat_amount,
            'subtotal_with_vat'   => (float) $this->subtotal_with_vat,

            // LQI-specific
            'contract_charge_id'  => $this->contract_charge_id,
            'impact'              => $this->impact, // 'add' | 'subtract'

            // Tax detail (si lo tenés)
            'tax_rate' => $this->whenLoaded('taxRate', function () {
                return [
                    'id'      => $this->taxRate->id,
                    'name'    => $this->taxRate->name,
                    'rate'    => (float) $this->taxRate->rate,
                    'included_in_vat_detail' => (bool) $this->taxRate->included_in_vat_detail,
                ];
            }),
        ];
    }
}
