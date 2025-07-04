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
        return [
            'id' => $this->id,
            'name' => $this->name,
            'prefix' => $this->prefix,
            'voucher_type_id' => $this->voucher_type_id,
            'sale_point_id' => $this->sale_point_id,
            'default_currency' => $this->default_currency,
            'next_number' => $this->next_number,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relaciones
            'voucher_type' => $this->whenLoaded('voucherType', fn() => new VoucherTypeResource($this->voucherType)),
            'sale_point' => $this->whenLoaded('salePoint', fn() => new SalePointResource($this->salePoint)),
        ];
    }
}
