<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractAdjustmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'contract_id'     => $this->contract_id,
            'effective_date'  => $this->effective_date?->format('Y-m-d'),
            'type'            => $this->type,

            'index_type_id'   => $this->index_type_id,
            'index_type'      => $this->whenLoaded('indexType', function () {
                return [
                    'id'          => $this->indexType->id,
                    'code'        => $this->indexType->code,
                    'name'        => $this->indexType->name,
                    'description' => $this->indexType->description,
                ];
            }),

            // Valores de cálculo
            'base_amount'     => $this->base_amount,
            'value'           => $this->value,   // % o coef según cálculo
            'factor'          => $this->factor,  // coeficiente aplicado
            'applied_amount'  => $this->applied_amount,
            'notes'           => $this->notes,

            // Auditoría de índice
            'index_audit' => [
                'start' => [
                    'date'  => $this->index_S_date?->format('Y-m-d'),
                    'value' => $this->index_S_value,
                ],
                'final' => [
                    'date'  => $this->index_F_date?->format('Y-m-d'),
                    'value' => $this->index_F_value,
                ],
            ],

            // Estado de aplicación
            'applied_at'      => $this->applied_at?->format('Y-m-d H:i:s'),

            // Timestamps
            'created_at'      => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'      => $this->updated_at?->format('Y-m-d H:i:s'),

            // Relaciones
            'contract' => $this->whenLoaded('contract', function () {
                return [
                    'id'             => $this->contract->id,
                    'start_date'     => $this->contract->start_date,
                    'end_date'       => $this->contract->end_date,
                    'monthly_amount' => $this->contract->monthly_amount,
                    'currency'       => $this->contract->currency,
                    'clients'        => $this->contract->clients->map(function ($contractClient) {
                        return [
                            'id'    => $contractClient->id,
                            'role'  => $contractClient->role,
                            'client' => [
                                'id'           => $contractClient->client->id,
                                'name'         => $contractClient->client->name,
                                'last_name'    => $contractClient->client->last_name,
                                'company_name' => $contractClient->client->company_name,
                                'client_type'  => $contractClient->client->client_type,
                            ],
                        ];
                    }),
                    // 👇 main tenant directo y plano
                    'main_tenant'    => $this->contract->relationLoaded('mainTenant') && $this->contract->mainTenant
                    ? [
                        'contract_client_id' => $this->contract->mainTenant->id,
                        'role'               => $this->contract->mainTenant->role,
                        'client' => [
                            'id'           => $this->contract->mainTenant->client->id,
                            'name'         => $this->contract->mainTenant->client->name,
                            'last_name'    => $this->contract->mainTenant->client->last_name,
                            'company_name' => $this->contract->mainTenant->client->company_name,
                            'client_type'  => $this->contract->mainTenant->client->client_type,
                            'full_name'  => $this->contract->mainTenant->client->full_name,
                        ],
                    ]
                    : null,
                ];
            }),

            'attachments' => $this->whenLoaded('attachments', function () {
                return $this->attachments->map(function ($attachment) {
                    return [
                        'id'                => $attachment->id,
                        'filename'          => $attachment->filename,
                        'original_filename' => $attachment->original_filename,
                        'mime_type'         => $attachment->mime_type,
                        'size'              => $attachment->size,
                        'url'               => $attachment->url,
                        'category'          => $attachment->category ? [
                            'id'   => $attachment->category->id,
                            'name' => $attachment->category->name,
                        ] : null,
                        'created_at'        => $attachment->created_at?->format('Y-m-d H:i:s'),
                    ];
                });
            }),
        ];
    }
}
