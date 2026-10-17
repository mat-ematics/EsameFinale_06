<?php

namespace App\Http\Controllers\api\v1;

use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Global\Translation\TranslationStoreRequest;
use App\Http\Requests\Global\Translation\TranslationUpdateRequest;
use App\Models\Global\CustomTranslation;
use App\Models\Global\EffectiveTranslation;
use App\Services\Global\TranslationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TranslationController extends Controller
{
    protected TranslationService $translationService;
 
    /**
     * Inizializzatore dei Servizi
     */
    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    /* --------- HELPER INTERNI ---------*/
 
    /**
     * Esegue la callback e converte qualsiasi eccezione in una risposta JSON
     */
    protected function run(Closure $callback)
    {
        try {
            return $callback();
        } catch (\Throwable $th) {
            return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
        }
    }

    protected function runCheckAdmin(Closure $callback)
    {
        if (Gate::allows('isAdmin')) {
            return $this->run($callback);
        } else {
            return AppHelpers::jsonResponseForbidden();
        }
    }

    /**
     * Lista di un tipo di traduzioni, con filtro opzionale per lingua
     */
    protected function listResponse(string $type, string|int|null $lang = null)
    {
        return $this->run(function () use ($type, $lang) {
            $langRecord = $lang !== null ? $this->translationService->resolveLanguage($lang) : null;
 
            $translations = $this->translationService->listTranslations($type, $langRecord);
            $data = $this->translationService->getTranslationCollectionInfo($translations, Gate::check('isAdmin'));
 
            return AppHelpers::jsonResponse('Translations successfully retrieved!', 200, $data);
        });
    }

    /**
     * Singola traduzione di un tipo, per lingua e chiave
     */
    protected function showResponse(string $type, string|int $lang, string $key)
    {
        return $this->run(function () use ($type, $lang, $key) {
            $langRecord = $this->translationService->resolveLanguage($lang);
            $translation = $this->translationService->findTranslationOrFail($type, $langRecord, $key);
 
            $resource = $this->translationService->getTranslationInfo($translation, Gate::check('isAdmin'));
 
            return AppHelpers::jsonResponse('Translation Successfully Retrieved', 200, $resource);
        });
    }

    /**
     * Aggiorna una traduzione esistente di un tipo
     */
    protected function updateResponse(string $type, string|int $lang, string $key, array $data)
    {
        return $this->runCheckAdmin(function () use ($type, $lang, $key, $data) {
            $langRecord = $this->translationService->resolveLanguage($lang);
            $translation = $this->translationService->findTranslationOrFail($type, $langRecord, $key);
 
            $updated = $this->translationService->updateTranslation($translation, $data);
 
            return AppHelpers::jsonResponse('Translation Successfully Updated', 200, $updated);
        });
    }

    /**
     * Elimina una traduzione di un tipo
     */
    protected function deleteResponse(string $type, string|int $lang, string $key, string $message)
    {
        return $this->runCheckAdmin(function () use ($type, $lang, $key, $message) {
            $langRecord = $this->translationService->resolveLanguage($lang);
            $translation = $this->translationService->findTranslationOrFail($type, $langRecord, $key);
 
            $this->translationService->deleteTranslation($translation);
 
            return AppHelpers::jsonResponse($message, 204);
        });
    }

     /* --------- LETTURA: EFFECTIVE (vista) ---------*/
 
    public function index()
    {
        return $this->listResponse(TranslationService::EFFECTIVE);
    }
 
    public function indexByLanguage(string|int $lang)
    {
        return $this->listResponse(TranslationService::EFFECTIVE, $lang);
    }
 
    public function show(string|int $lang, string $key)
    {
        return $this->showResponse(TranslationService::EFFECTIVE, $lang, $key);
    }

    /* --------- LETTURA: DEFAULT E CUSTOM ---------*/
 
    public function indexDefault()
    {
        return $this->listResponse(TranslationService::DEFAULT);
    }
 
    public function showDefault(string|int $lang, string $key)
    {
        return $this->showResponse(TranslationService::DEFAULT, $lang, $key);
    }
 
    public function indexCustom()
    {
        return $this->listResponse(TranslationService::CUSTOM);
    }
 
    public function showCustom(string|int $lang, string $key)
    {
        return $this->showResponse(TranslationService::CUSTOM, $lang, $key);
    }

    /* --------- SCRITTURA: DEFAULT ---------*/
 
    public function createDefault(TranslationStoreRequest $request, string|int $lang)
    {
        return $this->runCheckAdmin(function () use ($request, $lang) {

            $langRecord = $this->translationService->resolveLanguage($lang);
 
            $translation = $this->translationService->createTranslation(
                TranslationService::DEFAULT,
                $langRecord,
                $request->validated(),
            );
 
            $resource = $this->translationService->getTranslationInfo($translation, true);
 
            return AppHelpers::jsonResponse('Translation Successfully Created', 201, $resource);
        });
    }

    public function updateDefault(TranslationUpdateRequest $request, string|int $lang, string $key)
    {
        return $this->updateResponse(TranslationService::DEFAULT, $lang, $key, $request->validated());
    }
 
    public function deleteDefault(string|int $lang, string $key)
    {
        return $this->deleteResponse(TranslationService::DEFAULT, $lang, $key, 'Translation Successfully Deleted');
    }

    /**
     * Crea o aggiorna l'override custom (PUT idempotente)
     */
    public function upsertCustom(TranslationUpdateRequest $request, string|int $lang, string $key)
    {
        return $this->runCheckAdmin(function () use ($request, $lang, $key) {
            $langRecord = $this->translationService->resolveLanguage($lang);
 
            $resource = $this->translationService->upsertCustomTranslation(
                $langRecord,
                $key,
                $request->validated()['value'],
            );
 
            return AppHelpers::jsonResponse('Custom Translation Successfully Saved', 200, $resource);
        });
    }
 
    public function deleteCustom(string|int $lang, string $key)
    {
        return $this->deleteResponse(
            TranslationService::CUSTOM,
            $lang,
            $key,
            'Custom Translation Removed, the default value is now used',
        );
    }
}
