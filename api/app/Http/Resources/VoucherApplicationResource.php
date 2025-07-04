<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherApplicationResource extends JsonResource
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
            'applied_to_id' => $this->applied_to_id,
            'amount' => $this->amount,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relaciones
            'voucher' => $this->whenLoaded('voucher', fn() => new VoucherResource($this->voucher)),
            'applied_to' => $this->whenLoaded('appliedTo', fn() => new VoucherResource($this->appliedTo)),
        ];
    }
}
