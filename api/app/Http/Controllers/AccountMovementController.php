<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class AccountMovementController extends Controller
{
    public function index(Client $client, Request $request)
    {
        $query = $client->accountMovements()->orderBy('date', 'desc');

        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }

        return $query->paginate($request->get('per_page', 25));
    }
}
