<?php

namespace App\Http\Controllers\api\v1;

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
        $continent = $this->continentService->getContinentCollectionInfo(Continent::all(), Gate::check('isAdmin'));
        if (empty($continent))
        {
            $continent = 'There are no Continents Present at the moment';
        }
        return AppHelpers::jsonResponse('Continents Successfully Retrieved', 200, $continent);
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
         if (ctype_digit((string) $continent)) {
            $continentRecord = Continent::find((int) $continent);
        } else {
            $continentRecord = Continent::getContinent($continent);
        }
        if (!$continentRecord) {
            return AppHelpers::jsonResponse('Continent Not Found', 404);
        }

        $continentResource = $this->continentService->getContinentInfo($continentRecord, Gate::check('isAdmin'));
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
