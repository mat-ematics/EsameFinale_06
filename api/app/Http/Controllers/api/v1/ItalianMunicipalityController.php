<?php

namespace App\Http\Controllers\api\v1;

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
    public function index()
    {
        $municipality = $this->municipalityService->getItalianMunicipalityCollectionInfo(ItalianMunicipality::all(), Gate::check('isAdmin'));
        if (empty($municipality))
        {
            $municipality = 'There are no Municipalities Present at the moment';
        }
        return AppHelpers::jsonResponse('Municipalities Successfully Retrieved', 200, $municipality);
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
         if (ctype_digit((string) $municipality)) {
            $municipalityRecord = ItalianMunicipality::find((int) $municipality);
        } else {
            $municipalityRecord = ItalianMunicipality::getItalianMunicipality($municipality);
        }
        if (!$municipalityRecord) {
            return AppHelpers::jsonResponse('Municipality Not Found', 404);
        }

        $municipalityResource = $this->municipalityService->getItalianMunicipalityInfo($municipalityRecord, Gate::check('isAdmin'));
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
