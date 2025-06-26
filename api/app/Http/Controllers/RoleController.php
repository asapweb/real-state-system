<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    /**
     * Display a listing of the roles.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $roles = Role::paginate(10); // Ejemplo de paginación
        return response()->json(['data' => $roles]);
    }

    /**
     * Display the specified role.
     *
     * @param  \Spatie\Permission\Models\Role  $role
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Role $role)
    {
        $role->load('permissions'); // Cargar los permisos del rol
        return response()->json(['data' => $role]);
    }

    /**
     * Store a newly created role in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:roles,name',
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array|exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $role = Role::create($request->only('name', 'description'));

        if ($request->has('permissions')) {
            $role->givePermissionTo($request->input('permissions'));
        }

        return response()->json(['data' => $role, 'message' => 'Rol creado exitosamente'], 201);
    }

    /**
     * Update the specified role in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Spatie\Permission\Models\Role  $role
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Role $role)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:roles,name,' . $role->id,
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array|exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $role->update($request->only('name', 'description'));

        if ($request->has('permissions')) {
            $role->syncPermissions($request->input('permissions'));
        } else {
            $role->syncPermissions([]); // Remover todos los permisos si no se envían
        }

        return response()->json(['data' => $role, 'message' => 'Rol actualizado exitosamente']);
    }

    /**
     * Remove the specified role from storage.
     *
     * @param  \Spatie\Permission\Models\Role  $role
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Role $role)
    {
        if ($role->name === 'administrador') { // Ejemplo de protección para el rol de administrador
            return response()->json(['message' => 'No se puede eliminar el rol de administrador'], 403);
        }

        $role->delete();
        return response()->json(['message' => 'Rol eliminado exitosamente']);
    }

    /**
     * Get all available permissions.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function permissions()
    {
        $permissions = Permission::all(['name', 'description', 'group']);
        return response()->json(['data' => $permissions]);
    }

    /**
     * Get the permissions assigned to a specific role.
     *
     * @param  \Spatie\Permission\Models\Role  $role
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRolePermissions(Role $role)
    {
        $permissions = $role->permissions()->get(['name', 'description', 'group']); // Obtener nombre y descripción
        return response()->json(['data' => $permissions]);
    }

    /**
     * Update the permissions for a specific role.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Spatie\Permission\Models\Role  $role
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateRolePermissions(Request $request, Role $role)
    {
        $validator = Validator::make($request->all(), [
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name', // Asegura que los nombres de los permisos existan
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $role->syncPermissions($request->input('permissions', [])); // Si no se envían permisos, se sincroniza con un array vacío

        return response()->json(['message' => 'Permisos del rol actualizados exitosamente']);
    }

}
