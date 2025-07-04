<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;
use App\Enums\PropertyStatus;
use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use Illuminate\Validation\Rules\Enum;
use App\Http\Resources\PropertyResource;


class PropertyController extends Controller
{
    public function index(Request $request)
    {
        // Parámetros de paginación y ordenamiento
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'id');
        $sortDirection = strtolower($request->input('sort_direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        // Validación básica de campos ordenables
        $allowedSorts = ['id', 'name', 'last_name', 'document_number', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        // Consulta base
        $query = Property::query();

        // Filtros
        if ($request->filled('search.property_type_id')) {
            $query->where('property_type_id', $request->search['property_type_id']);
        }

        if ($request->filled('search.status')) {
            $query->where('status', $request->search['status']);
        }

        if ($request->has('search.text')) {
            $names = explode(' ', $request->search['text']);
            foreach ($names as $index => $item) {
                $query->where(function ($q) use($item){
                    $q->where('street', 'like', "%{$item}%")
                    ->orWhere('tax_code', 'like', "%{$item}%")
                    ->orWhere('registry_number', 'like', "%{$item}%");
                });
            }
        }

       // Relaciones
        $query->with(['propertyType', 'country', 'state', 'city', 'neighborhood']);

        // Orden y paginación
        $query->orderBy($sortBy, $sortDirection);
        return PropertyResource::collection($query->paginate($perPage));
    }

    public function store(StorePropertyRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        $property = Property::create($data);

        return response()->json($property->load(['propertyType', 'country', 'state', 'city', 'neighborhood']), 201);
    }

    public function update(UpdatePropertyRequest $request, Property $property)
    {
        $data = $request->validated();

        $property->update($data);

        return response()->json($property->load(['propertyType', 'country', 'state', 'city', 'neighborhood']));
    }

    public function show(Property $property)
    {
        return new PropertyResource($property->load([
            'propertyType',
            'country',
            'state',
            'city',
            'neighborhood'
        ]));
    }

    public function destroy(Property $property)
    {
        $property->delete();
        return response()->noContent();
    }
}
