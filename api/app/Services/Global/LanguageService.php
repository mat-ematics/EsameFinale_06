<?php

namespace App\Services\Global;

use App\Helpers\AppHelpers;
use App\Http\Resources\Global\Language\LanguageCollection;
use App\Http\Resources\Global\Language\LanguageResource;
use App\Models\Global\Language;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LanguageService {

    /**
     * Ritorna le informazioni associate alla categoria passata
     */
    public function getLanguageInfo(Language $lang, bool $isAdmin = false) : LanguageResource
    {
        return new LanguageResource($lang, $isAdmin);
    }

    /**
     * Ritorna le informazioni della collection di categorie passate
     */
    public function getLanguageCollectionInfo(Collection|array $lang, bool $isAdmin = false) : array
    {
        return (new LanguageCollection($lang, $isAdmin))->toArray();
    }

    /**
     * Crea una nuova categoria e ne ritorna il Model.
     * Attenzione: i dati non sono validati all'interno della funzione
     */
    public function createLanguage(array $data) : Language
    {
        return DB::transaction(function () use ($data) {
            
            //Creazione Categoria

            $newLanguage = Language::create([
                'name' => $data['name'],
                'abbreviation' => $data['abbreviation'],
                'locale' => $data['locale'] ?? null,
            ]);

            return $newLanguage;
        });
    }

     /**
     * Aggiorna le informazioni della categoria passata
     */
    public function updateLanguage(Language $lang, array $data) : LanguageResource
    {
        $data = array_filter($data);

        //Filtra i valori nulli
        $updateData = Arr::only($data, ['name', 'abbreviation', 'locale']);
        
        if (array_key_exists('value', $data)) {
            $updateData['value'] = $data['value']; // puo' essere null - ignorato se non passato
        }

        //Aggiorna Username e/o password se presenti
        if (!empty($updateData)) {
            $lang->update($updateData);
        }

        return $this->getLanguageInfo($lang, true);
    }

    /**
     * Elimina la Categoria Passata
     */
    public function deleteLanguage(Language $lang)
    {
        return $lang->deleteOrFail();
    }
}