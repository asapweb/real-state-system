<?php

namespace App\Http\Controllers;

use App\Models\ServiceType;
use App\Http\Resources\ServiceTypeResource;

class ServiceTypeController extends Controller
{
    /**
     * Endpoint público para listar tipos de servicio activos.
     */
    public function index()
    {
        $types = ServiceType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return ServiceTypeResource::collection($types);
    }
}
