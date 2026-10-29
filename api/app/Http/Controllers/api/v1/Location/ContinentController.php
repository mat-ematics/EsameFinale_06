<?php

namespace App\Http\Controllers\api\v1\Location;

use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Models\Location\Continent;
use App\Services\Location\ContinentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ContinentController extends Controller
{
    protected ContinentService $continentService;
    
    /**
     * Inizializzatore dei Servizi
     */
    public function __construct(ContinentService $continentService)
    {
        $this->continentService = $continentService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $continents = $this->continentService->getContinentCollectionInfo(Continent::all(), Gate::check('isAdmin'));
        return AppHelpers::jsonResponse(
            empty($continents) ? 'Continents Successfully Retrieved!' : "There are no Continents present at the moment",
            200, 
            $continents ?: [],
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
    public function show(int|string $continent)
    {
        $record = AppHelpers::findByKey(Continent::class, $continent, 'getContinent');

        $continentResource = $this->continentService->getContinentInfo($record, Gate::check('isAdmin'));
        return AppHelpers::jsonResponse('Continent Successfully Retrieved', 200, $continentResource);
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
