<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
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
            'location' => $this->location,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relaciones
            'users' => UserResource::collection($this->whenLoaded('users')),
            'department' => new DepartmentResource($this->whenLoaded('department')),
        ];
    }
}
