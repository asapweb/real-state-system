<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LqiResource extends JsonResource
{
    public function toArray($request): array
    {
        $period = $this->period ? \Illuminate\Support\Carbon::parse($this->period)->format('Y-m') : null;

        return [
            'id'         => $this->id,
            'status'     => $this->status,            // draft|issued|cancelled
            'period'     => $period,                  // YYYY-MM
            'currency'   => $this->currency,
            'issue_date' => $this->issue_date?->toDateString(),
            'due_date'   => $this->due_date?->toDateString(),

            // Identificación funcional
            'contract_id' => $this->contract_id,

            // Cliente (opcional, si lo guardás en el voucher)
            'client' => [
                'id'   => $this->client_id,
                'name' => $this->client_name,
            ],

            // Totales
            'totals' => [
                'subtotal_exempt'      => (float) ($this->subtotal_exempt ?? 0),
                'subtotal_untaxed'     => (float) ($this->subtotal_untaxed ?? 0),
                'subtotal_taxed'       => (float) ($this->subtotal_taxed ?? 0),
                'subtotal_vat'         => (float) ($this->subtotal_vat ?? 0),
                'subtotal_other_taxes' => (float) ($this->subtotal_other_taxes ?? 0),
                'subtotal'             => (float) ($this->subtotal ?? 0),
                'total'                => (float) ($this->total ?? 0),
            ],

            // Items
            'items' => LqiItemResource::collection($this->whenLoaded('items')),

            // Type info (por si lo necesitás en front)
            'type' => $this->whenLoaded('booklet.voucherType', function () {
                return [
                    'id'          => $this->booklet->voucherType->id,
                    'code'        => $this->booklet->voucherType->code,        // ej: LQI
                    'short_name'  => $this->booklet->voucherType->short_name,  // ej: LIQ
                    'name'        => $this->booklet->voucherType->name,
                    'credit'      => (bool) $this->booklet->voucherType->credit,
                    'affects_account' => (bool) $this->booklet->voucherType->affects_account,
                    'affects_cash'    => (bool) $this->booklet->voucherType->affects_cash,
                ];
            }),

            // Auditoría mínima
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
