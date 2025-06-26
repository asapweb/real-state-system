<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;

class DocumentTypeController extends Controller
{
    public function index()
    {
        return DocumentType::orderBy('name')->get();
    }
}
