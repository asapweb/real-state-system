<?php

namespace App\Http\Controllers;

use App\Models\IndexType;
use Illuminate\Http\Request;

class IndexTypeController extends Controller
{
    public function index()
    {
        return IndexType::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
