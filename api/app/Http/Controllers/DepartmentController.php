<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use App\Http\Resources\DepartmentResource;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Department::query();

        // Ordenamiento
        if ($request->has('sortBy')) {
            foreach ($request->sortBy as $sort) {
                $query->orderBy($sort['key'], $sort['order']);
            }
        } else {
            $query->orderBy('name', 'asc');
        }
        // return response()->json($request->has('search.name'));
        // return response()->json($request->search['name']);
        // Filtros
        if ($request->has('search.name')) {
            $query->where('name',  'like', '%'.$request->search['name'] .'%');
        }

         // Paginación
         $departments = $query->paginate($request->get('itemsPerPage', 10));

         return DepartmentResource::collection($departments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required',
            'location' => 'required'
        ]);

        $department = Department::create($fields);

        return [ 'department' => $department];
    }

    /**
     * Display the specified resource.
     */
    public function show(Department $department)
    {
        return new DepartmentResource($department);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Department $department)
    {
        $fields = $request->validate([
            'name' => 'required',
            'location' => 'required'
        ]);

        $department->update($fields);

        return [ 'department' => $department];
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Department $department)
    {
        $department->delete();

        return ['message' => "El departamento fue eliminado"];
    }
}
