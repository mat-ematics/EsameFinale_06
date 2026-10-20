<?php

namespace App\Services\Location;

use App\Helpers\AppHelpers;
use App\Http\Resources\Location\ItalianProvince\ItalianProvinceCollection;
use App\Http\Resources\Location\ItalianProvince\ItalianProvinceResource;
use App\Models\Location\ItalianProvince;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ItalianProvinceService {

    /**
     * Ritorna le informazioni associate alla categoria passata
     */
    public function getItalianProvinceInfo(ItalianProvince $province, bool $isAdmin = false) : ItalianProvinceResource
    {
        return new ItalianProvinceResource($province, $isAdmin);
    }

    /**
     * Ritorna le informazioni della collection di categorie passate
     */
    public function getItalianProvinceCollectionInfo(Collection|array $province, bool $isAdmin = false) : array
    {
        return (new ItalianProvinceCollection($province, $isAdmin))->toArray();
    }
}