<?php

namespace App\Http\Controllers;

use App\Models\AfipOperationType;
use App\Http\Resources\AfipOperationTypeResource;
use Illuminate\Http\Request;

class AfipOperationTypeController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $query = AfipOperationType::query();
        $query->orderBy('name');
        return AfipOperationTypeResource::collection($query->paginate($perPage));
    }
}
