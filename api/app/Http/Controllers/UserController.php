<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function changePassword(Request $request)
  {
    $request->validate([
      'current_password' => 'required',
      'password' => 'required|confirmed|min:8'
    ]);

    $user = $request->user();

    if (!Hash::check($request->current_password, $user->password)) {
      return response()->json(['message' => 'La contraseña actual es incorrecta'], 400);
    }

    $user->password = Hash::make($request->password);
    $user->save();

    return response()->json(['message' => 'Contraseña cambiada exitosamente']);
  }



    public function attachDepartment(User $user, Department $department)
    {
        $user->departments()->attach($department);

        return response()->json(['message' => 'Departamento vinculado correctamente']);
    }


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::query();
        // Ordenamiento
        if ($request->has('sortBy')) {
            foreach ($request->sortBy as $sort) {
                $query->orderBy($sort['key'], $sort['order']);
            }
        } else {
            $query->orderBy('name', 'asc');
        }
        // Filtros
        if ($request->has('search.name')) {
            $query->where('name',  'like', '%'.$request->search['name'] .'%');
        }

         // Paginación
         $users  = $query->with(['departments', 'client'])->paginate($request->get('itemsPerPage', 10));

         return response()->json($users );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'departments' => 'nullable|array', // Los departamentos son opcionales y deben ser un array
            'departments.*' => 'exists:departments,id', // Asegura que los IDs de departamento existan
        ]);
        $user = User::create($fields);
        $user->departments()->attach($request->input('departments')); // Guarda la relación con los departamentos
        return response()->json($user, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($user)
    {
        return response()->json(User::with(['departments', 'client'])->findOrFail($user));
    }

    /**
     * Get the roles of a specific user.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserRoles(User $user)
    {
        $roles = $user->roles()->get(['name']); // Obtener solo los nombres de los roles
        return response()->json(['data' => $roles]);
    }

    /**
     * Update the roles of a specific user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUserRoles(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'roles' => 'required|array',
            'roles.*' => 'string|exists:roles,name', // Asegura que los nombres de los roles existan
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->syncRoles($request->input('roles'));

        return response()->json(['message' => 'Roles del usuario actualizados exitosamente']);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $user->id, // Ignora el email actual
            'password' => 'nullable|min:6', // La contraseña es opcional en la actualización
            'departments' => 'nullable|array'
        ]);


        $user->update($data);
        $user->departments()->sync($request->input('departments')); // Guarda la relación con los departamentos
        return response()->json(['message' => 'Usuario actualizado']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        try {
            $user->delete();
            return response()->json(['message' => 'Usuario eliminado correctamente']);
        } catch (QueryException $e) { // O IntegrityConstraintViolationException
            // Código de error 1451: "Cannot delete or update a parent row"
            if ($e->getCode() === '23000') {
                return response()->json(['message' => 'Este usuario no puede ser eliminado porque tiene registros relacionados.'], 400); // Código de estado 400 (Bad Request)
            }

            // Para otros errores de base de datos
            return response()->json(['error' => 'Error al eliminar usuario.'], 500); // Código de estado 500 (Internal Server Error)
        }
    }

    public function restore($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();
        return response()->json(['message' => 'User restored successfully.']);
    }
}
