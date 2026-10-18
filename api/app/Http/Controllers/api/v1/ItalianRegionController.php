<?php

namespace App\Http\Controllers\api\v1;

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
        $region = $this->regionService->getItalianRegionCollectionInfo(ItalianRegion::all(), Gate::check('isAdmin'));
        if (empty($region))
        {
            $region = 'There are no Regions Present at the moment';
        }
        return AppHelpers::jsonResponse('Regions Successfully Retrieved', 200, $region);
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
         if (ctype_digit((string) $region)) {
            $regionRecord = ItalianRegion::find((int) $region);
        } else {
            $regionRecord = ItalianRegion::getItalianRegion($region);
        }
        if (!$regionRecord) {
            return AppHelpers::jsonResponse('Region Not Found', 404);
        }

        $regionResource = $this->regionService->getItalianRegionInfo($regionRecord, Gate::check('isAdmin'));
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
