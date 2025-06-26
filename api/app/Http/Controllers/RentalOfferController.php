<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRentalOfferRequest;
use App\Http\Requests\UpdateRentalOfferRequest;
use App\Models\RentalOffer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RentalOfferController extends Controller
{
    public function index(Request $request)
    {
        // Parámetros de paginación y ordenamiento
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'id');
        $sortDirection = strtolower($request->input('sort_direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        // Validación de campos ordenables
        $allowedSorts = ['id', 'price', 'availability_date', 'published_at', 'status', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        // Consulta base
        $query = RentalOffer::query();

        // Filtros
        if ($request->filled('search.property_id')) {
            $query->where('property_id', $request->search['property_id']);
        }

        if ($request->filled('search.status')) {
            $query->where('status', $request->search['status']);
        }

        if ($request->filled('search.availability_date_from')) {
            $query->whereDate('availability_date', '>=', $request->search['availability_date_from']);
        }

        if ($request->filled('search.availability_date_to')) {
            $query->whereDate('availability_date', '<=', $request->search['availability_date_to']);
        }

        if ($request->has('search.text')) {
            $terms = explode(' ', $request->search['text']);
            foreach ($terms as $term) {
                $query->whereHas('property', function ($q) use ($term) {
                    $q->where('street', 'like', "%{$term}%")
                      ->orWhere('registry_number', 'like', "%{$term}%")
                      ->orWhere('tax_code', 'like', "%{$term}%");
                });
            }
        }

        $query->with('property', 'property.neighborhood', 'property.city');

        // Orden y paginación
        $query->orderBy($sortBy, $sortDirection);
        return response()->json($query->paginate($perPage));
    }

    public function store(StoreRentalOfferRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();

            $offer = RentalOffer::create($data);

            $property = $offer->property;

            if ($property) {
                foreach ($property->services as $service) {
                    $offer->serviceStatuses()->create([
                        'property_service_id' => $service->id,
                        'is_active' => $service->is_active ?? false,
                        'paid_by' => $service->paid_by ?? null,
                        'has_debt' => false,
                        'debt_amount' => 0,
                        'notes' => $service->notes,
                    ]);
                }
            }

            return response()->json(
                $offer->load(['property', 'serviceStatuses.propertyService']),
                201
            );
        });
    }


    public function show(RentalOffer $rentalOffer)
    {
        $rentalOffer->load(['property', 'attachments', 'rentalApplications']);

        return response()->json($rentalOffer);
    }

    public function update(UpdateRentalOfferRequest $request, RentalOffer $rentalOffer)
    {
        $rentalOffer->update($request->validated());

        return response()->json($rentalOffer->load('property'));
    }

    public function destroy(RentalOffer $rentalOffer)
    {
        $rentalOffer->delete();

        return response()->json(['message' => 'Rental offer deleted']);
    }
}


