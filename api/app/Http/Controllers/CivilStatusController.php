<?php

namespace App\Http\Controllers;

use App\Models\CivilStatus;

class CivilStatusController extends Controller
{
    public function index()
    {
        return CivilStatus::orderBy('name')->get();
    }
}
