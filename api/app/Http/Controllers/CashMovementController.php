<?php

namespace App\Http\Controllers;

use App\Models\CashMovement;
use Illuminate\Http\Request;

class CashMovementController extends Controller
{
    public function index(Request $request)
    {
        $query = CashMovement::with(['voucher', 'paymentMethod'])->orderBy('date', 'desc');

        if ($request->filled('payment_method_id')) {
            $query->where('payment_method_id', $request->payment_method_id);
        }

        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        return $query->paginate($request->get('per_page', 25));
    }
}
