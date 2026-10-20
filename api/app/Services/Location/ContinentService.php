<?php

namespace App\Services\Location;

use App\Helpers\AppHelpers;
use App\Http\Resources\Location\Continent\ContinentCollection;
use App\Http\Resources\Location\Continent\ContinentResource;
use App\Models\Location\Continent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ContinentService {

    /**
     * Ritorna le informazioni associate alla categoria passata
     */
    public function getContinentInfo(Continent $continent, bool $isAdmin = false) : ContinentResource
    {
        return new ContinentResource($continent, $isAdmin);
    }

    /**
     * Ritorna le informazioni della collection di categorie passate
     */
    public function getContinentCollectionInfo(Collection|array $continent, bool $isAdmin = false) : array
    {
        return (new ContinentCollection($continent, $isAdmin))->toArray();
    }
}