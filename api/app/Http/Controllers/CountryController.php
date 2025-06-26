<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function index(Request $request)
    {
        return Country::orderBy('name')->get(['id', 'name', 'is_default']);
    }
}
