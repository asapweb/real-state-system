<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherTypeResource extends JsonResource
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
            'short_name' => $this->short_name,
            'letter' => $this->letter,
            'afip_id' => $this->afip_id,
            'credit' => (bool) $this->credit,
            'affects_account' => (bool) $this->affects_account,
            'affects_cash' => (bool) $this->affects_cash,
            'order' => $this->order,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'full_code' => $this->fullCode(),
        ];
    }
}
