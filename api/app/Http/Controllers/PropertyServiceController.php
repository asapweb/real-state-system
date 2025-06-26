<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyService;
use App\Models\PropertyOwner;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PropertyServiceController extends Controller
{
    public function index(Request $request, Property $property)
    {
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'id');
        $sortDirection = strtolower($request->input('sort_direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'service_type', 'account_number', 'provider_name', 'owner_name', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        $query = $property->services();

        if ($request->filled('search.service_type')) {
            $query->where('service_type', 'like', '%' . $request->search['service_type'] . '%');
        }

        $query->orderBy($sortBy, $sortDirection);

        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request, Property $property)
    {
        $data = $request->validate([
            'service_type' => [
                'required',
                'string',
                Rule::unique('property_services')->where(function ($query) use ($property) {
                    return $query->where('property_id', $property->id);
                })
            ],
            'account_number' => 'required|string|max:100',
            'provider_name' => 'required|string|max:100',
            'owner_name' => 'required|string|max:100',
            'is_active' => ['required', 'boolean'],
        ]);
        
        $service = new PropertyService($data);
        $service->property_id = $property->id;
        $service->save();
        // return response()->json('aqui 2 ', 500);

        return response()->json($service, 201);
    }


    public function update(Request $request, Property $property, PropertyService $service)
    {
        if ($service->property_id !== $property->id) {
            return response()->json(['message' => 'Servicio no asociado a esta propiedad.'], 403);
        }

        $data = $request->validate([
            'service_type' => [
                'required',
                'string',
                Rule::unique('property_services')
                    ->where(fn($query) => $query->where('property_id', $property->id))
                    ->ignore($service->id)
            ],
            'account_number' => 'required|string|max:100',
            'provider_name' => 'required|string|max:100',
            'owner_name' => 'required|string|max:100',
            'is_active' => ['required', 'boolean'],
        ]);

        $service->update($data);

        return response()->json($service);
    }

    public function destroy(Property $property, PropertyService $service)
    {
        if ($service->property_id !== $property->id) {
            return response()->json(['message' => 'Servicio no asociado a esta propiedad.'], 403);
        }

        $service->delete();

        return response()->json(['message' => 'Servicio eliminado con éxito.']);
    }


}