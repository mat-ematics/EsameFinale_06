<?php

namespace App\Services\Location;

use App\Helpers\AppHelpers;
use App\Http\Resources\Location\ItalianMunicipality\ItalianMunicipalityCollection;
use App\Http\Resources\Location\ItalianMunicipality\ItalianMunicipalityResource;
use App\Models\Location\ItalianMunicipality;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ItalianMunicipalityService {

    /**
     * Ritorna le informazioni associate alla categoria passata
     */
    public function getItalianMunicipalityInfo(ItalianMunicipality $municipality, bool $isAdmin = false) : ItalianMunicipalityResource
    {
        return new ItalianMunicipalityResource($municipality, $isAdmin);
    }

    /**
     * Ritorna le informazioni della collection di categorie passate
     */
    public function getItalianMunicipalityCollectionInfo(Collection|array $municipality, bool $isAdmin = false) : array
    {
        return (new ItalianMunicipalityCollection($municipality, $isAdmin))->toArray();
    }
}