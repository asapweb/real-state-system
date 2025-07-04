<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherItemResource extends JsonResource
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
            'voucher_id' => $this->voucher_id,
            'type' => $this->type,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'subtotal' => $this->subtotal,
            'vat_amount' => $this->vat_amount,
            'tax_rate_id' => $this->tax_rate_id,
            'subtotal_with_vat' => $this->subtotal_with_vat,
            'meta' => $this->meta,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relaciones
            'voucher' => $this->whenLoaded('voucher', fn() => new VoucherResource($this->voucher)),
        ];
    }
}
