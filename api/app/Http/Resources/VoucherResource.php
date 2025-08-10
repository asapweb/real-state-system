<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherResource extends JsonResource
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
            'booklet_id' => $this->booklet_id,
            'number' => $this->number,
            'issue_date' => $this->issue_date?->format('Y-m-d'),
            'period' => $this->period?->format('Y-m-d'),
            'generated_from_collection' => $this->generated_from_collection,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'client_id' => $this->client_id,
            'client_name' => $this->client_name,
            'client_address' =>  $this->client_address,
            'client_document_type_name' => $this->client_document_type_name,
            'client_document_number' => $this->client_document_number,
            'client_tax_condition_name' => $this->client_tax_condition_name,
            'client_tax_id_number' => $this->client_tax_id_number,
            'contract_id' => $this->contract_id,
            'status' => $this->status,
            'currency' => $this->currency,
            'total' => $this->total,
            'notes' => $this->notes,
            'meta' => $this->meta,
            'cae' => $this->cae,
            'cae_expires_at' => $this->cae_expires_at?->format('Y-m-d'),
            'subtotal_taxed' => $this->subtotal_taxed,
            'subtotal_untaxed' => $this->subtotal_untaxed,
            'subtotal_exempt' => $this->subtotal_exempt,
            'subtotal_vat' => $this->subtotal_vat,
            'subtotal' => $this->subtotal,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'full_number' => $this->full_number,
            'afip_operation_type_id' => $this->afip_operation_type_id,
            'service_date_from' => $this->service_date_from?->format('Y-m-d'),
            'service_date_to' => $this->service_date_to?->format('Y-m-d'),

            // Totales calculados
            'applications_total' => $this->whenLoaded('applications', function () {
                return $this->applications->sum(function ($app) {
                    $target = $app->appliedTo ?? $app->appliedVoucher ?? null;
                    $sign = ($target && $target->voucherType->credit) ? -1 : 1;
                    return $sign * $app->amount;
                });
            }),
            'items_total' => $this->whenLoaded('items', function () {
                return $this->items->sum('subtotal');
            }),
            'payments_total' => $this->whenLoaded('payments', function () {
                return $this->payments->sum('amount');
            }),
            'associations_total' => $this->whenLoaded('associations', function () {
                return $this->associations->sum('amount');
            }),

            // Relaciones
            'client' => new ClientResource($this->whenLoaded('client')),
            'contract' => new ContractResource($this->whenLoaded('contract')),
            'booklet' => new BookletResource($this->whenLoaded('booklet')),
            'items' => VoucherItemResource::collection($this->whenLoaded('items')),
            'payments' => VoucherPaymentResource::collection($this->whenLoaded('payments')),
            'afip_operation_type' => new AfipOperationTypeResource($this->whenLoaded('afipOperationType')),
            'applications_received' => VoucherApplicationResource::collection($this->whenLoaded('applicationsReceived')),
            'applications' => $this->whenLoaded('applications', function () {
                return $this->applications->map(function ($application) {
                    $appliedVoucher = $application->appliedTo;
                    return [
                        'id' => $application->id,
                        'applied_to_id' => $application->applied_to_id,
                        'applied_voucher_full_number' => optional($appliedVoucher)->full_number,
                        'applied_voucher_type' => optional($appliedVoucher?->voucherType)->short_name,
                        'applied_voucher_issue_date' => optional($appliedVoucher)->issue_date?->format('Y-m-d'),
                        'applied_voucher_total' => optional($appliedVoucher)->total,
                        'applied_voucher_applied' => optional($appliedVoucher)->applicationsReceived?->sum('amount') ?? 0,
                        'applied_voucher_pending' => optional($appliedVoucher)->total - (optional($appliedVoucher)->applicationsReceived?->sum('amount') ?? 0),
                        'amount' => $application->amount,
                    ];
                });
            }),
            'associations' => $this->whenLoaded('associations', function () {
                return $this->associations->map(function ($association) {
                    return [
                        'id' => $association->id,
                        'associated_voucher_id' => $association->associated_voucher_id,
                        'description' => $association->description,
                        'associated_voucher_full_number' => optional($association->associatedVoucher)->full_number,
                        'associated_voucher_type' => optional($association->associatedVoucher?->voucherType)->short_name,
                    ];
                });
            }),
            // Campos calculados
            'formatted_number' => $this->booklet?->voucherType?->code . '-' . $this->number,
            'voucher_type' => $this->booklet?->voucherType?->code,
            'voucher_type_name' => $this->booklet?->voucherType?->name,
            'is_collection' => $this->booklet?->voucherType?->code === 'COB',
            'is_paid' => $this->status === 'issued',
            'is_canceled' => $this->status === 'canceled',
            'paid_at' => $this->meta['paid_at'] ?? null,
            'paid_by_user_id' => $this->meta['paid_by_user_id'] ?? null,
        ];
    }
}
