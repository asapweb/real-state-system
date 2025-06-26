<?php

namespace App\Http\Controllers;

use App\Models\RentalOffer;
use App\Models\RentalOfferServiceStatus;
use Illuminate\Http\Request;
use App\Http\Requests\UpdateRentalOfferServiceStatusRequest;

class RentalOfferServiceStatusController extends Controller
{
    public function index(RentalOffer $rentalOffer)
    {
        $services = $rentalOffer->serviceStatuses()->with('propertyService')->get();
        return response()->json(['data' => $services]);
    }

    public function update(UpdateRentalOfferServiceStatusRequest $request, RentalOfferServiceStatus $rentalOfferServiceStatus)
    {
        $rentalOfferServiceStatus->update($request->validated());
        return response()->json($rentalOfferServiceStatus->load('propertyService'));
    }
}
