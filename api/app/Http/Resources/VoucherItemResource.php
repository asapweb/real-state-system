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
            'type' => $this->type?->value,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'subtotal' => $this->subtotal,
            'vat_amount' => $this->vat_amount,
            'tax_rate_id' => $this->tax_rate_id,
            'subtotal_with_vat' => $this->subtotal_with_vat,
            'meta' => $this->meta,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),

            // Campos calculados
            'type_name' => $this->type?->value,
            'is_service' => $this->type?->value === 'service',
            'is_rent' => $this->type?->value === 'expense-rent',
            'is_insurance' => $this->type?->value === 'insurance',
            'is_commission' => $this->type?->value === 'commission',
            'is_penalty' => $this->type?->value === 'penalty',
            'is_late_fee' => $this->type?->value === 'late_fee',

            // Relaciones
            'voucher' => $this->whenLoaded('voucher', fn() => new VoucherResource($this->voucher)),
            'tax_rate' => $this->whenLoaded('taxRate', function () {
                return [
                    'id' => $this->taxRate->id,
                    'name' => $this->taxRate->name,
                    'rate' => $this->taxRate->rate,
                    'description' => $this->taxRate->description,
                ];
            }),
        ];
    }
}
