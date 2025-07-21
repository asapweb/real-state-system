<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IndexValueResource extends JsonResource
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
            'index_type_id' => $this->index_type_id,
            'index_type' => $this->whenLoaded('indexType'),
            'effective_date' => $this->effective_date,
            'value' => $this->value,
            'percentage' => $this->percentage,
            'calculation_mode' => $this->indexType?->calculation_mode,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
