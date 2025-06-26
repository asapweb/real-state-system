<?php

namespace App\Http\Controllers;

use App\Models\RentalApplication;
use App\Models\RentalApplicationClient;
use Illuminate\Http\Request;
use App\Http\Requests\StoreRentalApplicationClientRequest;
use App\Http\Requests\UpdateRentalApplicationClientRequest;
use Illuminate\Validation\Rule;

class RentalApplicationClientController extends Controller
{
    public function index(Request $request, RentalApplication $rentalApplication)
    {
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'id');
        $sortDirection = strtolower($request->input('sort_direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'income', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        $query = $rentalApplication->rentalApplicationClients()->with('client');

        if ($request->filled('search.client')) {
            $text = $request->search['client'];
            $query->whereHas('client', function ($q) use ($text) {
                $q->where('name', 'like', "%{$text}%")
                  ->orWhere('last_name', 'like', "%{$text}%");
            });
        }

        $query->orderBy($sortBy, $sortDirection);

        return response()->json($query->paginate($perPage));
    }

    public function store(StoreRentalApplicationClientRequest $request, RentalApplication $rentalApplication)
    {
        $data = $request->validated();
        $data['rental_application_id'] = $rentalApplication->id;

        $client = RentalApplicationClient::create($data);

        return response()->json($client->load('client'), 201);
    }

    public function update(UpdateRentalApplicationClientRequest $request, RentalApplication $rentalApplication, RentalApplicationClient $rentalApplicationClient)
    {
        if ($rentalApplicationClient->rental_application_id !== $rentalApplication->id) {
            return response()->json(['message' => 'Cliente no asociado a esta solicitud.'], 403);
        }

        $rentalApplicationClient->update($request->validated());

        return response()->json($rentalApplicationClient->load('client'));
    }

    public function destroy(RentalApplication $rentalApplication, RentalApplicationClient $rentalApplicationClient)
    {
        if ($rentalApplicationClient->rental_application_id !== $rentalApplication->id) {
            return response()->json(['message' => 'Cliente no asociado a esta solicitud.'], 403);
        }

        $rentalApplicationClient->delete();

        return response()->json(['message' => 'Cliente eliminado correctamente.']);
    }
}
