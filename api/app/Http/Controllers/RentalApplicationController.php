<?php

namespace App\Http\Controllers;

use App\Models\RentalApplication;
use Illuminate\Http\Request;
use App\Http\Requests\StoreRentalApplicationRequest;
use App\Http\Requests\UpdateRentalApplicationRequest;

class RentalApplicationController extends Controller
{
    public function index(Request $request)
{
    // Parámetros de paginación y ordenamiento
    $perPage = $request->input('per_page', 10);
    $sortBy = $request->input('sort_by', 'id');
    $sortDirection = strtolower($request->input('sort_direction', 'asc')) === 'desc' ? 'desc' : 'asc';

    // Validación básica de campos ordenables
    $allowedSorts = ['id', 'status', 'reservation_amount', 'created_at'];
    if (!in_array($sortBy, $allowedSorts)) {
        $sortBy = 'id';
    }

    // Consulta base
    $query = RentalApplication::query();

    // Filtros
    if ($request->filled('search.property_id')) {
        $query->where('property_id', $request->search['property_id']);
    }

    if ($request->filled('search.applicant_id')) {
        $query->where('applicant_id', $request->search['applicant_id']);
    }

    if ($request->filled('search.status')) {
        $query->where('status', $request->search['status']);
    }

    if ($request->filled('search.rental_offer_id')) {
        $query->where('rental_offer_id', $request->search['rental_offer_id']);
    }

     // Filtros
        // if ($request->has('search.text')) {
        //     $names = explode(' ', $request->search['text']);
        //     foreach ($names as $item) {
        //         $query->where(function ($q) use ($item) {
        //             $q->orWhere('id', "{$item}");
        //         });
        //     }
        // }

    if ($request->has('search.text')) {
        $words = explode(' ', $request->search['text']);
        $query->whereIn('id', $words);
        // $query->where('id', $request->search['text']);
        // $words = explode(' ', $request->search['text']);
        // foreach ($words as $word) {
        //     $query->whereHas('property', function ($q) use ($word) {
        //         $q->where('street', 'like', "%{$word}%")
        //           ->orWhere('registry_number', 'like', "%{$word}%")
        //           ->orWhere('tax_code', 'like', "%{$word}%");
        //     });
        // }
    }

    // Relaciones necesarias
    $query->with(['property', 'property.neighborhood', 'property.city', 'applicant', 'rentalOffer']);

    // Orden y paginación
    $query->orderBy($sortBy, $sortDirection);
    return response()->json($query->paginate($perPage));
}


    public function store(StoreRentalApplicationRequest $request)
    {
        $application = RentalApplication::create($request->validated());
        return response()->json($application->load(['property', 'applicant', 'rentalOffer']), 201);
    }

    public function show(RentalApplication $rentalApplication)
    {
        return $rentalApplication->load(['property', 'applicant', 'rentalOffer', 'attachments']);
    }

    public function update(UpdateRentalApplicationRequest $request, RentalApplication $rentalApplication)
    {
        $rentalApplication->update($request->validated());
        return response()->json($rentalApplication->load(['property', 'applicant', 'rentalOffer']));
    }

    public function destroy(RentalApplication $rentalApplication)
    {
        $rentalApplication->delete();
        return response()->noContent();
    }
}
