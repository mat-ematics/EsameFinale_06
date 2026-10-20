<?php

namespace App\Services\Location;

use App\Helpers\AppHelpers;
use App\Http\Resources\Location\Country\CountryCollection;
use App\Http\Resources\Location\Country\CountryResource;
use App\Models\Location\Country;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CountryService {

    /**
     * Ritorna le informazioni associate alla categoria passata
     */
    public function getCountryInfo(Country $country, bool $isAdmin = false) : CountryResource
    {
        return new CountryResource($country, $isAdmin);
    }

    /**
     * Ritorna le informazioni della collection di categorie passate
     */
    public function getCountryCollectionInfo(Collection|array $country, bool $isAdmin = false) : array
    {
        return (new CountryCollection($country, $isAdmin))->toArray();
    }
}