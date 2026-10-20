<?php

namespace App\Services\Location;

use App\Helpers\AppHelpers;
use App\Http\Resources\Location\ItalianRegion\ItalianRegionCollection;
use App\Http\Resources\Location\ItalianRegion\ItalianRegionResource;
use App\Models\Location\ItalianRegion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ItalianRegionService {

    /**
     * Ritorna le informazioni associate alla categoria passata
     */
    public function getItalianRegionInfo(ItalianRegion $region, bool $isAdmin = false) : ItalianRegionResource
    {
        return new ItalianRegionResource($region, $isAdmin);
    }

    /**
     * Ritorna le informazioni della collection di categorie passate
     */
    public function getItalianRegionCollectionInfo(Collection|array $region, bool $isAdmin = false) : array
    {
        return (new ItalianRegionCollection($region, $isAdmin))->toArray();
    }
}