<?php

namespace App\Http\Controllers;

use App\Models\IndexType;
use Illuminate\Http\Request;
use App\Http\Resources\IndexTypeResource;
use Illuminate\Http\JsonResponse;
use App\Enums\CalculationMode;
use App\Enums\IndexFrequency;

class IndexTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'id');
        $sortDirection = strtolower($request->input('sort_direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'code', 'name', 'is_active', 'calculation_mode', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        $query = IndexType::query();

        // Filtros
        if ($request->filled('search.name')) {
            $query->where('name', 'like', '%' . $request->input('search.name') . '%');
        }

        if ($request->filled('search.code')) {
            $query->where('code', 'like', '%' . $request->input('search.code') . '%');
        }

        if ($request->filled('search.calculation_mode')) {
            $query->where('calculation_mode', $request->input('search.calculation_mode'));
        }

        $query->orderBy($sortBy, $sortDirection);

        return IndexTypeResource::collection($query->paginate($perPage))->response();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:index_types,code',
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
            'calculation_mode' => 'required|in:' . implode(',', array_column(CalculationMode::cases(), 'value')),
            'frequency' => 'required|in:' . implode(',', array_column(IndexFrequency::cases(), 'value')),
        ]);

        $indexType = IndexType::create($validated);

        return (new IndexTypeResource($indexType))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(IndexType $indexType): JsonResponse
    {
        return (new IndexTypeResource($indexType))->response();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, IndexType $indexType): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'sometimes|required|string|max:255|unique:index_types,code,' . $indexType->id,
            'name' => 'sometimes|required|string|max:255',
            'is_active' => 'sometimes|boolean',
            'calculation_mode' => 'sometimes|required|in:' . implode(',', array_column(CalculationMode::cases(), 'value')),
            'frequency' => 'sometimes|required|in:' . implode(',', array_column(IndexFrequency::cases(), 'value')),
        ]);

        $indexType->update($validated);

        return (new IndexTypeResource($indexType))->response();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(IndexType $indexType): JsonResponse
    {
        $indexType->delete();

        return response()->json(['message' => 'Index type deleted successfully']);
    }
}
