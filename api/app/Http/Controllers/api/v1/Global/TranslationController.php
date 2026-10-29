<?php

namespace App\Http\Controllers\api\v1\Global;

use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Global\Translation\TranslationStoreRequest;
use App\Http\Requests\Global\Translation\TranslationUpdateRequest;
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
 
    private function listResponse(string $type, string|int|null $lang = null)
    {
        $langRecord   = $lang !== null ? $this->translationService->resolveLanguage($lang) : null;
        $translations = $this->translationService->listTranslations($type, $langRecord);
        $data         = $this->translationService->getTranslationCollectionInfo($translations, Gate::check('isAdmin'));

        return AppHelpers::jsonResponse(
            empty($data) ? 'There are no Translations at the moment' : 'Translations Successfully Retrieved',
            200,
            $data ?: []
        );
    }

    /** One translation of a type, by language and key */
    private function showResponse(string $type, string|int $lang, string $key)
    {
        $translation = $this->findTranslation($type, $lang, $key);
        $resource    = $this->translationService->getTranslationInfo($translation, Gate::check('isAdmin'));

        return AppHelpers::jsonResponse('Translation Successfully Retrieved', 200, $resource);
    }

    private function findTranslation(string $type, string|int $lang, string $key)
    {
        $langRecord = $this->translationService->resolveLanguage($lang);
        return $this->translationService->findTranslationOrFail($type, $langRecord, $key);
    }

    /* --------- READ: EFFECTIVE (view) --------- */

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

    /* --------- READ: DEFAULT AND CUSTOM --------- */

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

    /* --------- WRITE (admin only, enforced by route middleware) --------- */

    public function createDefault(TranslationStoreRequest $request, string|int $lang)
    {
        $translation = $this->translationService->createTranslation(
            TranslationService::DEFAULT,
            $this->translationService->resolveLanguage($lang),
            $request->validated(),
        );

        $resource = $this->translationService->getTranslationInfo($translation, true);
        return AppHelpers::jsonResponse('Translation Successfully Created', 201, $resource);
    }

    public function updateDefault(TranslationUpdateRequest $request, string|int $lang, string $key)
    {
        $translation = $this->findTranslation(TranslationService::DEFAULT, $lang, $key);
        $updated     = $this->translationService->updateTranslation($translation, $request->validated());

        return AppHelpers::jsonResponse('Translation Successfully Updated', 200, $updated);
    }

    public function deleteDefault(string|int $lang, string $key)
    {
        $translation = $this->findTranslation(TranslationService::DEFAULT, $lang, $key);
        $this->translationService->deleteTranslation($translation);

        return AppHelpers::jsonResponse('Translation Successfully Deleted', 200);
    }

    /** Creates or updates the custom override (idempotent PUT) */
    public function upsertCustom(TranslationUpdateRequest $request, string|int $lang, string $key)
    {
        $resource = $this->translationService->upsertCustomTranslation(
            $this->translationService->resolveLanguage($lang),
            $key,
            $request->validated()['value'],
        );

        return AppHelpers::jsonResponse('Custom Translation Successfully Saved', 200, $resource);
    }

    public function deleteCustom(string|int $lang, string $key)
    {
        $translation = $this->findTranslation(TranslationService::CUSTOM, $lang, $key);
        $this->translationService->deleteTranslation($translation);

        return AppHelpers::jsonResponse('Custom Translation Removed, the default value is now used', 200);
    }
}
