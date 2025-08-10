<?php

namespace App\Http\Controllers;

use App\Models\PropertyType;
use App\Http\Resources\PropertyTypeResource;
use Illuminate\Http\Request;

class PropertyTypeController extends Controller
{
    public function index(Request $request) 
    {
        return PropertyTypeResource::collection(PropertyType::orderBy('name')->get());
    }
}
