<?php

namespace App\Http\Controllers;

use App\Models\IndexValue;
use App\Models\IndexType;
use App\Services\IndexValueService;
use App\Http\Requests\StoreIndexValueRequest;
use App\Http\Requests\UpdateIndexValueRequest;
use Illuminate\Http\Request;
use App\Http\Resources\IndexValueResource;
use Illuminate\Http\JsonResponse;

class IndexValueController extends Controller
{
    protected $indexValueService;

    public function __construct(IndexValueService $indexValueService)
    {
        $this->indexValueService = $indexValueService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'effective_date');
        $sortDirection = strtolower($request->input('sort_direction', 'desc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'index_type_id', 'effective_date', 'value', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'effective_date';
        }

        $query = IndexValue::with('indexType');

        // Filtros
        if ($request->filled('index_type_id')) {
            $query->where('index_type_id', $request->input('index_type_id'));
        }
        if ($request->filled('effective_date_from')) {
            $query->where('effective_date', '>=', $request->input('effective_date_from'));
        }
        if ($request->filled('effective_date_to')) {
            $query->where('effective_date', '<=', $request->input('effective_date_to'));
        }

        $query->orderBy($sortBy, $sortDirection);

        return IndexValueResource::collection($query->paginate($perPage))->response();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreIndexValueRequest $request): JsonResponse
    {
        try {
            $indexValue = $this->indexValueService->create($request->validated());

            return (new IndexValueResource($indexValue))
                ->response()
                ->setStatusCode(201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el valor de índice'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(IndexValue $indexValue): JsonResponse
    {
        $indexValue->load('indexType');

        return (new IndexValueResource($indexValue))->response();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateIndexValueRequest $request, IndexValue $indexValue): JsonResponse
    {
        try {
            $updatedIndexValue = $this->indexValueService->update($indexValue, $request->validated());

            return (new IndexValueResource($updatedIndexValue))->response();
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el valor de índice'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(IndexValue $indexValue): JsonResponse
    {
        try {
            $this->indexValueService->delete($indexValue);

            return response()->json(['message' => 'Index value deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el valor de índice'
            ], 500);
        }
    }

    /**
     * Get values by calculation mode
     */
    public function getByCalculationMode(Request $request, string $calculationMode): JsonResponse
    {
        try {
            $filters = $request->only(['index_type_id', 'effective_date_from', 'effective_date_to']);
            $values = $this->indexValueService->getByFilters($filters);

            return IndexValueResource::collection($values)->response();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener valores por modo de cálculo'
            ], 500);
        }
    }

    /**
     * Get the latest value for a specific index type
     */
    public function getLatestValue(int $indexTypeId): JsonResponse
    {
        try {
            $latestValue = $this->indexValueService->getLatestValue($indexTypeId);

            if (!$latestValue) {
                return response()->json([
                    'message' => 'No se encontraron valores para este tipo de índice'
                ], 404);
            }

            return (new IndexValueResource($latestValue))->response();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener el último valor'
            ], 500);
        }
    }
}
