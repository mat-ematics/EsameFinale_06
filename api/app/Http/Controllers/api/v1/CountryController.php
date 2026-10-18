<?php

namespace App\Http\Controllers\api\v1;

use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Models\Location\Country;
use App\Services\Location\CountryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CountryController extends Controller
{
    protected CountryService $countryService;
    
    /**
     * Inizializzatore dei Servizi
     */
    public function __construct(CountryService $countryService)
    {
        $this->countryService = $countryService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $country = $this->countryService->getCountryCollectionInfo(Country::all(), Gate::check('isAdmin'));
        if (empty($country))
        {
            $country = 'There are no Countries Present at the moment';
        }
        return AppHelpers::jsonResponse('Countries Successfully Retrieved', 200, $country);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(int|string $country)
    {
         if (ctype_digit((string) $country)) {
            $countryRecord = Country::find((int) $country);
        } else {
            $countryRecord = Country::getCountry($country);
        }
        if (!$countryRecord) {
            return AppHelpers::jsonResponse('Country Not Found', 404);
        }

        $countryResource = $this->countryService->getCountryInfo($countryRecord, Gate::check('isAdmin'));
        return AppHelpers::jsonResponse('Country Successfully Retrieved', 200, $countryResource);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
