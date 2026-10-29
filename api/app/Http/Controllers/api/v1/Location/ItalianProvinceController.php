<?php

namespace App\Http\Controllers\api\v1\Location;

use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Models\Location\ItalianProvince;
use App\Services\Location\ItalianProvinceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ItalianProvinceController extends Controller
{
    protected ItalianProvinceService $provinceService;
    
    /**
     * Inizializzatore dei Servizi
     */
    public function __construct(ItalianProvinceService $provinceService)
    {
        $this->provinceService = $provinceService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $provinces = $this->provinceService->getItalianProvinceCollectionInfo(ItalianProvince::all(), Gate::check('isAdmin'));
        return AppHelpers::jsonResponse(
            empty($provinces) ? 'Provinces Successfully Retrieved!' : "There are no Provinces present at the moment",
            200, 
            $provinces ?: [],
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
    public function show(int|string $province)
    {
        $record = AppHelpers::findByKey(ItalianProvince::class, $province, 'getProvince');

        $provinceResource = $this->provinceService->getItalianProvinceInfo($record, Gate::check('isAdmin'));
        return AppHelpers::jsonResponse('Province Successfully Retrieved', 200, $provinceResource);
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
