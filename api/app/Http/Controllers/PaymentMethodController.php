<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Http\Resources\PaymentMethodResource;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        $query = PaymentMethod::query();
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }
        $query->with('defaultCashAccount');
        $methods = $query->orderBy('name')->get();
        return PaymentMethodResource::collection($methods);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:payment_methods,name',
            'requires_reference' => 'required|boolean',
            'default_cash_account_id' => 'nullable|exists:cash_accounts,id',
            'is_active' => 'boolean',
        ]);
        $method = PaymentMethod::create($validated);
        return new PaymentMethodResource($method->load('defaultCashAccount'));
    }

    public function show(PaymentMethod $paymentMethod)
    {
        $paymentMethod->load('defaultCashAccount');
        return new PaymentMethodResource($paymentMethod);
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:payment_methods,name,' . $paymentMethod->id,
            'requires_reference' => 'required|boolean',
            'default_cash_account_id' => 'nullable|exists:cash_accounts,id',
            'is_active' => 'boolean',
        ]);
        $paymentMethod->update($validated);
        return new PaymentMethodResource($paymentMethod->load('defaultCashAccount'));
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        $paymentMethod->delete();
        return response()->noContent();
    }

    public function active()
    {
        $methods = PaymentMethod::with('defaultCashAccount')
        // ->where('is_active', true)
        ->orderBy('name')->get();
        return PaymentMethodResource::collection($methods);
    }
}
