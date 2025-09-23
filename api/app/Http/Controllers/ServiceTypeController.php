<?php

namespace App\Http\Controllers;

use App\Models\ServiceType;
use Illuminate\Http\Request;
use App\Http\Resources\ServiceTypeResource;

class ServiceTypeController extends Controller
{
    public function index(Request $request)
    {
        $q = ServiceType::query();

        // Filtros
        $active = $request->has('active')
            ? filter_var($request->input('active'), FILTER_VALIDATE_BOOLEAN)
            : true; // por defecto solo activos
        $q->where('is_active', $active);

        if ($request->filled('code')) {
            $codes = (array) $request->input('code');
            $codes = array_map(fn($c) => strtoupper($c), $codes);
            $q->whereIn('code', $codes);
        }

        if ($request->filled('q')) {
            $term = $request->input('q');
            $q->where(function ($qq) use ($term) {
                $qq->where('name', 'like', "%{$term}%")
                   ->orWhere('code', 'like', "%".strtoupper($term)."%");
            });
        }

        // Orden
        $q->orderBy('name');

        // Paginación opcional
        if (filter_var($request->input('all', false), FILTER_VALIDATE_BOOLEAN)) {
            return ServiceTypeResource::collection($q->get());
        }

        $perPage = (int) $request->input('per_page', 50);
        return ServiceTypeResource::collection($q->paginate($perPage));
    }
}
