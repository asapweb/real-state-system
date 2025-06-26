<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyOwner;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PropertyOwnerController extends Controller
{
    public function index(Request $request, Property $property)
    {
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'id');
        $sortDirection = strtolower($request->input('sort_direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'ownership_percentage', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        $query = $property->owners()->with('client');

        if ($request->filled('search.client')) {
            $name = $request->search['client'];
            $query->whereHas('client', function ($q) use ($name) {
                $q->where('name', 'like', "%$name%")
                  ->orWhere('last_name', 'like', "%$name%");
            });
        }

        $query->orderBy($sortBy, $sortDirection);

        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request, Property $property)
    {
        $data = $request->validate([
            'client_id' => [
                'required',
                'exists:clients,id',
                Rule::unique('property_owners')->where(function ($query) use ($property) {
                    return $query->where('property_id', $property->id);
                })
            ],
            'ownership_percentage' => 'required|numeric|min:0|max:100',
        ]);

        $owner = new PropertyOwner($data);
        $owner->property_id = $property->id;
        $owner->save();

        return response()->json($owner->load('client'), 201);
    }

    public function update(Request $request, Property $property, PropertyOwner $owner)
    {
        // Verificamos que el propietario pertenezca al inmueble
        if ($owner->property_id !== $property->id) {
            return response()->json(['message' => 'Propietario no asociado a esta propiedad.'], 403);
        }

        $data = $request->validate([
            'client_id' => [
                'required',
                'exists:clients,id',
                Rule::unique('property_owners')->where(function ($query) use ($property, $owner) {
                    return $query->where('property_id', $property->id)
                                ->where('id', '!=', $owner->id); // excluir al actual
                }),
            ],
            'ownership_percentage' => 'required|numeric|min:0|max:100',
        ]);

        $owner->update($data);

        return response()->json($owner->load('client'));
    }
    public function destroy(Property $property, PropertyOwner $owner)
    {
        // Verificamos que el propietario pertenezca al inmueble
        if ($owner->property_id !== $property->id) {
            return response()->json(['message' => 'Propietario no asociado a esta propiedad.'], 403);
        }

        $owner->delete();

        return response()->json(['message' => 'Propietario eliminado correctamente.']);
    }

}