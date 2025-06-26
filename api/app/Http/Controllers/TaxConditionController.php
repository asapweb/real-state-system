<?php

namespace App\Http\Controllers;

use App\Models\TaxCondition;

class TaxConditionController extends Controller
{
    public function index()
    {
        return TaxCondition::orderBy('name')->get();
    }
}
