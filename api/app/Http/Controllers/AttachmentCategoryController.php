<?php

namespace App\Http\Controllers;

use App\Models\AttachmentCategory;
use Illuminate\Http\Request;
use App\Http\Resources\AttachmentCategoryResource;

class AttachmentCategoryController extends Controller
{
    public function index(Request $request)
    {
        // Parámetros de paginación y ordenamiento
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'id');
        $sortDirection = strtolower($request->input('sort_direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        // Validación básica de campos ordenables
        $allowedSorts = ['name', 'context', 'is_required', 'is_default', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        // Consulta base
        $query = AttachmentCategory::query();

        // Filtros
        if ($request->has('search.name')) {
            $names = explode(' ', $request->search['name']);
            foreach ($names as $item) {
                $query->where(function ($q) use ($item) {
                    $q->where('name', 'like', "%{$item}%");
                    // ->orWhere('last_name', 'like', "%{$item}%")
                    // ->orWhere('document_number', 'like', "%{$item}%");
                });
            }
        }

        if ($request->filled('context')) {
            $query->where('context', $request->context);
        }

        // Relaciones
        // $query->with([]);

        // Orden y paginación
        $query->orderBy($sortBy, $sortDirection);
        return AttachmentCategoryResource::collection($query->paginate($perPage));
    }

    public function all(Request $request)
    {
        $query = AttachmentCategory::query();

        if ($request->filled('context')) {
            $query->where('context', $request->context);
        }

        return $query->orderBy('name')->get();
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'context' => 'nullable|string|max:255',
            'is_required' => 'boolean',
            'is_default' => 'boolean',
        ]);

        $category = AttachmentCategory::create($data);

        return response()->json($category, 201);
    }

    public function show(AttachmentCategory $attachmentCategory)
    {
        return new AttachmentCategoryResource($attachmentCategory);
    }

    public function update(Request $request, AttachmentCategory $attachmentCategory)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'context' => 'nullable|string|max:255',
            'is_required' => 'boolean',
            'is_default' => 'boolean',
        ]);

        $attachmentCategory->update($data);

        return response()->json($attachmentCategory);
    }


    public function destroy(AttachmentCategory $attachmentCategory)
    {
        $attachmentCategory->delete();

        return response()->json(['message' => 'Eliminado correctamente']);
    }

}
