<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceTypeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'        => $this->id,
            'code'      => $this->code,
            'name'      => $this->name,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
