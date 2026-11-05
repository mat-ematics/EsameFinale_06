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

    public static function searchMunicipality (array $data): array {

        $query = ItalianMunicipality::query()
            ->leftJoin('italian_provinces as p', 'p.id', '=', 'italian_municipalities.province_id')
            ->select([
                'italian_municipalities.id',
                'italian_municipalities.name',
                'italian_municipalities.cap',
                'italian_municipalities.province_id',
                'p.region_id',
                'p.code as province_code',
            ])
            ->when($data['province_id'] ?? null,
                fn ($q, $id) => $q->where('italian_municipalities.province_id', $id));

        if (!empty($data['cap'])) {
            $query->where('italian_municipalities.cap', $data['cap'])
                ->orderBy('italian_municipalities.name');
        } else {
            $like = addcslashes($data['search'], '%_\\');
            $query->where('italian_municipalities.name', 'like', "%{$like}%")
                ->orderByRaw('CASE WHEN italian_municipalities.name LIKE ? THEN 0 ELSE 1 END', ["{$like}%"])
                ->orderBy('italian_municipalities.name');
        }

        $rows = $query
            ->limit($data['limit'] ?? (empty($data['cap'])) ? 10 : 25)
            ->get()
            ->map(fn ($m) => [
                'id'          => $m->id,
                'name'        => $m->name,
                'label'       => $m->province_code ? "{$m->name} ({$m->province_code})" : $m->name,
                'cap'         => $m->cap,
                'province_id' => $m->province_id,
                'region_id'   => $m->region_id,
            ]);

        return $rows->all();
    }
}