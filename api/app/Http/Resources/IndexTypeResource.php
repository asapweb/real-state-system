<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IndexTypeResource extends JsonResource
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
            'code' => $this->code,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'calculation_mode' => $this->calculation_mode,
            'frequency' => $this->frequency,
            'is_cumulative' => $this->is_cumulative,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
