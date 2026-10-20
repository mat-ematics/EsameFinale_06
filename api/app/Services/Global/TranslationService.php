<?php

namespace App\Services\Global;

use App\Helpers\AppHelpers;
use App\Http\Resources\Global\Translation\TranslationCollection;
use App\Http\Resources\Global\Translation\TranslationResource;
use App\Models\Global\CustomTranslation;
use App\Models\Global\EffectiveTranslation;
use App\Models\Global\Language;
use App\Models\Global\Translation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TranslationService {

    public const EFFECTIVE = 'effective'; // vista: custom + default (sola lettura)
    public const DEFAULT = 'default';     // tabella translations
    public const CUSTOM = 'custom';       // tabella custom_translations
 
    /* --------- HELPER INTERNI ---------*/
 
    /**
     * Ritorna la classe Model da usare per le scritture (la vista e' esclusa)
     */
    protected function writableModel(string $type) : string
    {
        return match ($type) {
            self::DEFAULT => Translation::class,
            self::CUSTOM => CustomTranslation::class,
            default => throw new HttpException(400, 'Invalid translation type'),
        };
    }
 
    /**
     * Ritorna la query di lettura per il tipo richiesto
     */
    protected function readQuery(string $type) : Builder
    {
        return $type === self::EFFECTIVE
            ? EffectiveTranslation::query()
            : $this->writableModel($type)::query();
    }
 
    /* --------- LINGUA ---------*/
 
    /**
     * Risolve la lingua da ID o abbreviazione, 404 se non esiste
     */
    public function resolveLanguage(string|int $lang) : Language
    {
        $record = ctype_digit((string) $lang)
            ? Language::find((int) $lang)
            : Language::getLanguage($lang);
 
        if (!$record) {
            throw new HttpException(404, 'Language Not Found');
        }
 
        return $record;
    }
 
    /* --------- LETTURA ---------*/
 
    /**
     * Ritorna le informazioni associate alla traduzione passata
     */
    public function getTranslationInfo(Translation|CustomTranslation|EffectiveTranslation $translation, bool $isAdmin = false) : TranslationResource
    {
        return new TranslationResource($translation, $isAdmin);
    }
 
    /**
     * Ritorna le informazioni della collection di traduzioni passate
     */
    public function getTranslationCollectionInfo(Collection|array $translations, bool $isAdmin = false) : array
    {
        return (new TranslationCollection($translations, $isAdmin))->toArray();
    }
 
    /**
     * Lista delle traduzioni di un tipo, filtrabile per lingua
     */
    public function listTranslations(string $type, ?Language $lang = null) : Collection
    {
        return $this->readQuery($type)
            ->with('language')
            ->when($lang, fn (Builder $q) => $q->where('language_id', $lang->id))
            ->get();
    }
 
    /**
     * Cerca una traduzione per lingua e chiave. Ritorna null se non esiste
     */
    public function findTranslation(string $type, Language $lang, string $key) : ?Model
    {
        return $this->readQuery($type)
            ->where('language_id', $lang->id)
            ->where('key', $key)
            ->first();
    }
 
    /**
     * Come findTranslation, ma con 404 se non esiste
     */
    public function findTranslationOrFail(string $type, Language $lang, string $key) : Model
    {
        $translation = $this->findTranslation($type, $lang, $key);
 
        if (!$translation) {
            throw new HttpException(404, 'Translation Not Found');
        }
 
        return $translation;
    }
 
    /* --------- SCRITTURA ---------*/
 
    /**
     * Crea una nuova traduzione (default o custom) e ne ritorna il Model.
     * Attenzione: i dati non sono validati all'interno della funzione
     */
    public function createTranslation(string $type, Language $lang, array $data) : Model
    {
        $model = $this->writableModel($type);
 
        return DB::transaction(function () use ($model, $lang, $data) {
 
            $exists = $model::where('language_id', $lang->id)
                ->where('key', $data['key'])
                ->exists();
 
            if ($exists) {
                throw new HttpException(409, 'Translation Already Exists');
            }
 
            return $model::create([
                'language_id' => $lang->id,
                'key' => $data['key'],
                'value' => $data['value'],
            ]);
        });
    }
 
    /**
     * Aggiorna il valore della traduzione passata.
     * Lingua e chiave identificano la traduzione, quindi non sono modificabili
     */
    public function updateTranslation(Model $translation, array $data) : TranslationResource
    {
        $translation->update(Arr::only($data, ['value']));
 
        return $this->getTranslationInfo($translation, true);
    }
 
    /**
     * Crea oppure aggiorna l'override custom per lingua e chiave
     */
    public function upsertCustomTranslation(Language $lang, string $key, string $value) : TranslationResource
    {
        $custom = CustomTranslation::updateOrCreate(
            ['language_id' => $lang->id, 'key' => $key],
            ['value' => $value],
        );
 
        return $this->getTranslationInfo($custom, true);
    }
 
    /**
     * Elimina la traduzione passata.
     * Se e' custom, la vista torna a mostrare il valore di default
     */
    public function deleteTranslation(Model $translation)
    {
        return $translation->deleteOrFail();
    }
}