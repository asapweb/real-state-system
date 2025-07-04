<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
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
            'client_id' => $this->client_id,
            'start_at' => $this->start_at,
            'booked_by' => $this->booked_by,
            'received_at' => $this->received_at,
            'received_by' => $this->received_by,
            'attended_start_at' => $this->attended_start_at,
            'attended_start_by' => $this->attended_start_by,
            'attended_end_at' => $this->attended_end_at,
            'attended_end_by' => $this->attended_end_by,
            'employee_id' => $this->employee_id,
            'department_id' => $this->department_id,
            'notes' => $this->notes,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,

            // Relaciones
            'client' => new ClientResource($this->whenLoaded('client')),
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'employee' => new UserResource($this->whenLoaded('employee')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'updator' => new UserResource($this->whenLoaded('updator')),
            'receiver' => new UserResource($this->whenLoaded('receiver')),
            'attendant_start' => new UserResource($this->whenLoaded('attendantStart')),
            'attendant_end' => new UserResource($this->whenLoaded('attendantEnd')),
        ];
    }
}
