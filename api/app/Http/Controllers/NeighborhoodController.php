<?php

namespace App\Http\Controllers;

use App\Models\Neighborhood;
use Illuminate\Http\Request;

class NeighborhoodController extends Controller
{
    public function index(Request $request)
    {
        $query = Neighborhood::query();

        if ($request->filled('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        return $query->orderBy('name')->get(['id', 'city_id', 'name', 'is_default']);
    }
}
