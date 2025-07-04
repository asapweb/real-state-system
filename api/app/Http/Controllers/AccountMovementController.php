<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use App\Http\Resources\AccountMovementResource;

class AccountMovementController extends Controller
{
    public function index(Client $client, Request $request)
    {
        $query = $client->accountMovements()->orderBy('date', 'desc');

        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }

        return AccountMovementResource::collection($query->paginate($request->get('per_page', 25)));
    }
}
