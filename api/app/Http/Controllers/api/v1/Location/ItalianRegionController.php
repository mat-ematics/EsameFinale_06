<?php

namespace App\Http\Controllers\api\v1\Location;

use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Models\Location\ItalianRegion;
use App\Services\Location\ItalianRegionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ItalianRegionController extends Controller
{
    protected ItalianRegionService $regionService;
    
    /**
     * Inizializzatore dei Servizi
     */
    public function __construct(ItalianRegionService $regionService)
    {
        $this->regionService = $regionService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $regions = $this->regionService->getItalianRegionCollectionInfo(ItalianRegion::all(), Gate::check('isAdmin'));
        return AppHelpers::jsonResponse(
            empty($regions) ? 'Regions Successfully Retrieved!' : "There are no Regions present at the moment",
            200, 
            $regions ?: [],
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
    public function show(int|string $region)
    {
        $record = AppHelpers::findByKey(ItalianRegion::class, $region, 'getRegion');

        $regionResource = $this->regionService->getItalianRegionInfo($record, Gate::check('isAdmin'));
        return AppHelpers::jsonResponse('Region Successfully Retrieved', 200, $regionResource);
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
