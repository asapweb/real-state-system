<?php

namespace App\Http\Controllers;

use App\Models\ChargeType;
use Illuminate\Http\Request;
use App\Http\Resources\ChargeTypeResource;

class ChargeTypeController extends Controller
{
    public function index(Request $request)
    {
        return \App\Http\Resources\ChargeTypeResource::collection(
            \App\Models\ChargeType::active()->orderBy('code')->get()
        );
    
       $q = ChargeType::active()->orderBy('code')->get();

        return ChargeTypeResource::collection($q); 
    }
}
