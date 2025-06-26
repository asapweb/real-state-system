<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function index(Request $request)
    {
        $query = City::query();

        if ($request->filled('state_id')) {
            $query->where('state_id', $request->state_id);
        }

        return $query->orderBy('name')->get(['id', 'state_id', 'name', 'is_default']);
    }
}
