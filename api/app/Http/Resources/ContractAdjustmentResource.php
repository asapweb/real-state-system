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
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            'effective_date' => $this->effective_date,
            'type' => $this->type,
            'index_type_id' => $this->index_type_id,
            'value' => $this->value,
            'applied_at' => $this->applied_at,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'contract' => $this->whenLoaded('contract', function () {
                return [
                    'id' => $this->contract->id,
                    'clients' => $this->contract->clients->map(function ($contractClient) {
                        return [
                            'id' => $contractClient->id,
                            'role' => $contractClient->role,
                            'client' => [
                                'id' => $contractClient->client->id,
                                'name' => $contractClient->client->name,
                                'last_name' => $contractClient->client->last_name,
                                'company_name' => $contractClient->client->company_name,
                                'client_type' => $contractClient->client->client_type,
                            ],
                        ];
                    }),
                ];
            }),

            'index_type' => $this->whenLoaded('indexType', function () {
                return [
                    'id' => $this->indexType->id,
                    'name' => $this->indexType->name,
                    'description' => $this->indexType->description,
                ];
            }),

            'attachments' => $this->whenLoaded('attachments', function () {
                return $this->attachments->map(function ($attachment) {
                    return [
                        'id' => $attachment->id,
                        'filename' => $attachment->filename,
                        'original_filename' => $attachment->original_filename,
                        'mime_type' => $attachment->mime_type,
                        'size' => $attachment->size,
                        'url' => $attachment->url,
                        'category' => $attachment->category ? [
                            'id' => $attachment->category->id,
                            'name' => $attachment->category->name,
                        ] : null,
                        'created_at' => $attachment->created_at,
                    ];
                });
            }),
        ];
    }
}
