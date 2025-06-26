<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register (Request $request) {
        $fields = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed',
        ]);

        $user = User::create($fields);
        $token = $user->createToken($request->email);

        return [
            "user" => $user,
            "token" => $token->plainTextToken
        ];

    }

    public function login (Request $request) {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::with(['departments'])->where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                 trans('auth.failed'),
            ]);
        }

        $token = $user->createToken($request->email)->plainTextToken;
        $roles = $user->getRoleNames()->toArray();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return [
            "data" => $user,
            "token" => $token,
            "roles" => $roles,
            "permissions" => $permissions,
        ];
    }

    public function logout (Request $request) {

        $request->user()->tokens()->delete();

        return ['message' => "You are logged out"];
        // return ['message' => $request->user()->tokens()->delete()];
    }
}
