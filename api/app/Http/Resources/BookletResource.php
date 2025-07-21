<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookletResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $salePointNumber = $this->getFormattedSalePointNumber();
        $displayName = !$salePointNumber ? $this->name :  $this->name . '-' . ($salePointNumber);

        return [
            'id' => $this->id,
            'name' => $displayName,
            'voucher_type_id' => $this->voucher_type_id,
            'sale_point_id' => $this->sale_point_id,
            'default_currency' => $this->default_currency,
            'next_number' => $this->next_number,
            'default' => (bool) $this->default,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),

            // Relaciones
            'voucher_type' => $this->whenLoaded('voucherType', fn() => new VoucherTypeResource($this->voucherType)),
            'sale_point' => $this->whenLoaded('salePoint', fn() => new SalePointResource($this->salePoint)),
            'formatted_sale_point_number' => $salePointNumber,
        ];
    }
}
