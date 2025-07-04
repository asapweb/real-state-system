<?php

namespace App\Http\Controllers;

use App\Models\State;
use Illuminate\Http\Request;
use App\Http\Resources\StateResource;

class StateController extends Controller
{
    public function index(Request $request)
    {
        $query = State::query();

        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        return StateResource::collection($query->orderBy('name')->get());
    }
}
