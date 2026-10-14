<?php

namespace App\Services\Global;

use App\Helpers\AppHelpers;
use App\Http\Resources\Global\ConfigResource;
use App\Http\Resources\Global\ConfigCollection;
use App\Models\Global\Config;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConfigService {

    /**
     * Ritorna le informazioni associate alla categoria passata
     */
    public function getConfigInfo(Config $config, bool $isAdmin = false) : ConfigResource
    {
        return new ConfigResource($config, $isAdmin);
    }

    /**
     * Ritorna le informazioni della collection di categorie passate
     */
    public function getConfigCollectionInfo(Collection|array $config, bool $isAdmin = false) : array
    {
        return (new ConfigCollection($config, $isAdmin))->toArray();
    }

     /**
     * Aggiorna le informazioni della categoria passata
     */
    public function updateConfig(Config $config, array $data) : ConfigResource
    {
        $data = array_filter($data);

        //Filtra i valori nulli
        $updateData = array_filter($data);
        
        if (array_key_exists('value', $data)) {
            $updateData['value'] = $data['value']; // puo' essere null - ignorato se non passato
        }

        //Aggiorna Username e/o password se presenti
        if (!empty($updateData)) {
            $config->update($updateData);
        }

        return $this->getConfigInfo($config, true);
    }
}