<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;
use App\Http\Resources\CityResource;

class CityController extends Controller
{
    public function index(Request $request)
    {
        $query = City::query();

        if ($request->filled('state_id')) {
            $query->where('state_id', $request->state_id);
        }

        return CityResource::collection($query->orderBy('name')->get());
    }
}
