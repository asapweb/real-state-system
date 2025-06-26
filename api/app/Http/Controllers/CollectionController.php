<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Illuminate\Http\Request;
use App\Services\CollectionGenerationService;
use Illuminate\Support\Carbon;
use App\Exceptions\CollectionGenerationException;
use App\Http\Requests\StoreCollectionRequest;

class CollectionController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'id');
        $sortDirection = strtolower($request->input('sort_direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'period', 'due_date', 'issue_date', 'total_amount', 'status', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        $query = Collection::query();

        // Filtros
        if ($request->filled('search.id')) {
            $query->where('id', $request->search['id']);
        }

        if ($request->filled('search.issue_date_start')) {
            $query->whereDate('issue_date', '>=', $request->search['issue_date_start']);
        }

        if ($request->filled('search.issue_date_end')) {
            $query->whereDate('issue_date', '<=', $request->search['issue_date_end']);
        }

        if ($request->filled('search.period')) {
            $query->where('period', $request->search['period']);
        }

        if ($request->filled('search.status')) {
            $query->where('status', $request->search['status']);
        }

        if ($request->filled('search.contract_id')) {
            $query->where('contract_id', $request->search['contract_id']);
        }

        if ($request->filled('search.client_id')) {
            $query->where('client_id', $request->search['client_id']);
        }

        // Relaciones
        $query->with(['client', 'contract']);

        // Orden y paginación
        $query->orderBy($sortBy, $sortDirection);

        return response()->json($query->paginate($perPage));
    }

    public function store(StoreCollectionRequest $request)
    {
        $validated = $request->validated();

        $collection = Collection::create([
            'client_id' => $validated['client_id'],
            'contract_id' => $validated['contract_id'] ?? null,
            'issue_date' => $validated['issue_date'],
            'due_date' => $validated['due_date'],
            'currency' => $validated['currency'],
            'is_automatic' => false,
        ]);

        foreach ($validated['items'] as $item) {
            $collection->items()->create([
                'type' => $item['type'],
                'description' => $item['description'] ?? '',
                'quantity' => $item['quantity'] ?? 1,
                'unit_price' => $item['unit_price'],
                'amount' => $item['quantity'] * $item['unit_price'],
                'currency' => $validated['currency'],
                'meta' => $item['meta'] ?? null,
            ]);
        }

        return response()->json($collection->load('items'), 201);
    }

    public function show(Collection $collection)
    {
        $collection->load(['items', 'contract', 'client']);
        return response()->json($collection);
    }

    public function generate(Request $request, CollectionGenerationService $service)
    {
        $request->validate([
            'period' => ['required', 'date_format:Y-m'],
        ]);

        $period = Carbon::createFromFormat('Y-m', $request->period);

        try {
            $count = $service->generateForMonth($period);

            // Puedes personalizar el mensaje según el resultado, si el servicio lo devuelve
            return response()->json([
                'message' => "Se generaron $count cobranzas para " . $period->translatedFormat('F Y'),
                'generated' => $count,
            ]);
        } catch (CollectionGenerationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors,
            ], 422);
        }
    }

    public function preview(Request $request, CollectionGenerationService $service)
    {
        $request->validate([
            'period' => ['required', 'date_format:Y-m'],
        ]);

        $period = Carbon::createFromFormat('Y-m', $request->period);

        return response()->json($service->previewForMonth($period));
    }

}
