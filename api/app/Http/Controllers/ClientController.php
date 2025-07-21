<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ClientResource;


class ClientController extends Controller
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
        $query = Client::query();

        // Filtros
        if ($request->has('search.name')) {
            $names = explode(' ', $request->search['name']);
            foreach ($names as $item) {
                $query->where(function ($q) use ($item) {
                    $q->where('name', 'like', "%{$item}%")
                    ->orWhere('last_name', 'like', "%{$item}%")
                    ->orWhere('document_number', 'like', "%{$item}%");
                });
            }
        }

        if ($request->filled('search.type')) {
            $query->where('type', $request->search['type']);
        }

        // Relaciones
        $query->with(['documentType', 'nationality', 'taxCondition']);

        // Orden y paginación
        $query->orderBy($sortBy, $sortDirection);
        return ClientResource::collection($query->paginate($perPage));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClientRequest $request)
    {
       $client = Client::create($request->validated());
        return response()->json($client, 201); // 201 Created
    }

    public function update(UpdateClientRequest $request, Client $client)
    {
        $client->update($request->validated());
        return response()->json($client); // 200 OK por convención
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return ['message' => "El cliente fue eliminado"];
    }

    public function show(Client $client)
    {
        $client->load([
            'documentType',
            'taxDocumentType',
            'taxCondition',
            'civilStatus',
            'nationality',
            'attachments',
        ]);

        return new ClientResource($client);
    }
}
