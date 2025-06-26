<?php

namespace App\Http\Controllers;

use App\Models\Country;

class NationalityController extends Controller
{
    public function index()
    {
        return Country::orderBy('name')->get();
    }
}
