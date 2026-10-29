<?php

namespace App\Http\Controllers\api\v1\Location;

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
        $countries = $this->countryService->getCountryCollectionInfo(Country::all(), Gate::check('isAdmin'));
        return AppHelpers::jsonResponse(
            empty($countries) ? 'Countries Successfully Retrieved!' : "There are no Countries present at the moment",
            200, 
            $countries ?: [],
        );
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
        $record = AppHelpers::findByKey(Country::class, $country, 'getCountry');

        $countryResource = $this->countryService->getCountryInfo($record, Gate::check('isAdmin'));
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
