<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\DocumentType;
use App\Models\Neighborhood;
use App\Models\PropertyType;
use App\Models\TaxCondition;

class ReferenceDataController extends Controller
{
    public function getPropertyTypes()
    {
        return PropertyType::select('id', 'name', 'default')->orderBy('name', 'asc')->get();
    }
    public function getCountries()
    {
        return Country::select('id', 'name', 'code', 'default')->orderBy('name', 'asc')->get();
    }

    public function getStates($countryId)
    {
        return State::where('country_id', $countryId)
            ->select('id', 'country_id', 'name', 'default')
            ->orderBy('name', 'asc')
            ->get();
    }
    public function getCities($stateId)
    {
        return City::where('state_id', $stateId)
            ->select('id', 'state_id', 'name', 'default')
            ->orderBy('name', 'asc')
            ->get();
    }
    public function getNeighborhoods($cityId)
    {
        return Neighborhood::where('city_id', $cityId)
            ->select('id', 'city_id', 'name', 'default')
            ->orderBy('name', 'asc')
            ->get();
    }

    public function getDocumentTypes()
    {
        return DocumentType::active()
            ->select('id', 'code', 'name', 'description', 'applies_to')
            ->get();
    }

    public function getTaxConditions()
    {
        return TaxCondition::active()
            ->select('id', 'code', 'name', 'description')
            ->get();
    }
}
