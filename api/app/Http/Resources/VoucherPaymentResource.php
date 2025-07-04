<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherPaymentResource extends JsonResource
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
            'payment_method_id' => $this->payment_method_id,
            'amount' => $this->amount,
            'reference' => $this->reference,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relaciones
            'voucher' => $this->whenLoaded('voucher', fn() => new VoucherResource($this->voucher)),
            'payment_method' => $this->whenLoaded('paymentMethod', fn() => new PaymentMethodResource($this->paymentMethod)),
        ];
    }
}
