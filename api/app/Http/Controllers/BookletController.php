<?php

namespace App\Http\Controllers;

use App\Models\Booklet;
use App\Http\Resources\BookletResource;
use Illuminate\Http\Request;

class BookletController extends Controller
{
    public function index(Request $request)
    {
        $query = Booklet::query();
        if ($request->filled('type')) {
            $query->whereHas('voucherType', function ($q) use ($request) {
                $q->where('short_name', $request->type);
            });
        }
        if ($request->filled('letter')) {
            $query->whereHas('voucherType', function ($q) use ($request) {
                $q->where('letter', $request->letter);
            });
        }
       
        $query->with('voucherType');
        return BookletResource::collection($query->orderBy('name')->get());
    }
}
