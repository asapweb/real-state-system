<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\Client;
use Illuminate\Http\Request;

class PropertyClientController extends Controller
{
    public function index(Property $property)
    {
        return $property->clients;
    }

    public function store(Request $request, Property $property)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'ownership_percentage' => 'required|numeric|min:0|max:100',
            'is_main_owner' => 'boolean',
            'ownership_start_date' => 'nullable|date',
            'ownership_end_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);
        $property->clients()->attach($validated['client_id'], [
            'ownership_percentage' => $validated['ownership_percentage'],
            'is_main_owner' => $validated['is_main_owner'] ?? false,
            'ownership_start_date' => $validated['ownership_start_date'] ?? null,
            'ownership_end_date' => $validated['ownership_end_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);
        return response()->json(['message' => 'Cliente asociado a la propiedad'], 201);
    }

    public function update(Request $request, Property $property, Client $client)
    {
        $validated = $request->validate([
            'ownership_percentage' => 'required|numeric|min:0|max:100',
            'is_main_owner' => 'boolean',
            'ownership_start_date' => 'nullable|date',
            'ownership_end_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);
        $property->clients()->updateExistingPivot($client->id, $validated);
        return response()->json(['message' => 'Datos de la relación actualizados']);
    }

    public function destroy(Property $property, Client $client)
    {
        $property->clients()->detach($client->id);
        return response()->noContent();
    }
}
