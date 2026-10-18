<?php

namespace App\Http\Controllers\api\v1;

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
        $province = $this->provinceService->getItalianProvinceCollectionInfo(ItalianProvince::all(), Gate::check('isAdmin'));
        if (empty($province))
        {
            $province = 'There are no Provinces Present at the moment';
        }
        return AppHelpers::jsonResponse('Provinces Successfully Retrieved', 200, $province);
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
         if (ctype_digit((string) $province)) {
            $provinceRecord = ItalianProvince::find((int) $province);
        } else {
            $provinceRecord = ItalianProvince::getItalianProvince($province);
        }
        if (!$provinceRecord) {
            return AppHelpers::jsonResponse('Province Not Found', 404);
        }

        $provinceResource = $this->provinceService->getItalianProvinceInfo($provinceRecord, Gate::check('isAdmin'));
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
