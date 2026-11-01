<?php

namespace App\Http\Controllers\api\v1\Location;

use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Models\Location\ItalianMunicipality;
use App\Services\Location\ItalianMunicipalityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ItalianMunicipalityController extends Controller
{
    protected ItalianMunicipalityService $municipalityService;
    
    /**
     * Inizializzatore dei Servizi
     */
    public function __construct(ItalianMunicipalityService $municipalityService)
    {
        $this->municipalityService = $municipalityService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $this->lookupSearch(ItalianMunicipality::class, $request);
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
    public function show(int|string $municipality)
    {
        $record = AppHelpers::findByKey(ItalianMunicipality::class, $municipality, 'getMunicipality');

        $municipalityResource = $this->municipalityService->getItalianMunicipalityInfo($record, Gate::check('isAdmin'));
        return AppHelpers::jsonResponse('Municipality Successfully Retrieved', 200, $municipalityResource);
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
