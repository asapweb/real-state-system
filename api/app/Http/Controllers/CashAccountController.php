<?php

namespace App\Http\Controllers;

use App\Models\CashAccount;
use App\Http\Resources\CashAccountResource;
use App\Http\Requests\StoreCashAccountRequest;
use App\Http\Requests\UpdateCashAccountRequest;
use Illuminate\Http\Request;

class CashAccountController extends Controller
{
    public function index(Request $request)
    {
        $query = CashAccount::query();
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }
        if ($request->filled('currency')) {
            $query->where('currency', $request->input('currency'));
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        $query->orderBy('name');
        $accounts = $query->paginate($request->input('per_page', 10));
        return CashAccountResource::collection($accounts);
    }

    public function store(StoreCashAccountRequest $request)
    {
        $account = CashAccount::create($request->validated());
        return new CashAccountResource($account);
    }

    public function show(CashAccount $cashAccount)
    {
        $cashAccount->load(['cashMovements' => function ($q) {
            $q->latest()->limit(10);
        }]);
        return new CashAccountResource($cashAccount);
    }

    public function update(UpdateCashAccountRequest $request, CashAccount $cashAccount)
    {
        $cashAccount->update($request->validated());
        return new CashAccountResource($cashAccount);
    }

    public function destroy(CashAccount $cashAccount)
    {
        $cashAccount->is_active = false;
        $cashAccount->save();
        return response()->noContent();
    }

    public function active()
    {
        $accounts = CashAccount::where('is_active', true)->orderBy('name')->get();
        return CashAccountResource::collection($accounts);
    }
}
