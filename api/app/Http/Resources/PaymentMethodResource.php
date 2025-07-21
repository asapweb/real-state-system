<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'requires_reference' => $this->requires_reference,
            'default_cash_account_id' => $this->default_cash_account_id,
            'default_cash_account' => CashAccountResource::make($this->whenLoaded('defaultCashAccount')),
        ];
    }
}
