<?php

namespace App\Http\Controllers;

use App\Models\PropertyType;
use Illuminate\Http\Request;

class PropertyTypeController extends Controller
{
    public function index(Request $request) 
    {
        return PropertyType::orderBy('name')->get(['id', 'name', 'is_default']);
    }
}
