<?php

namespace App\Http\Controllers\api\v1\Global;

use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Global\Language\LanguageStoreRequest;
use App\Http\Requests\Global\Language\LanguageUpdateRequest;
use App\Models\Global\Language;
use App\Services\Global\LanguageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LanguageController extends Controller
{
    protected LanguageService $languageService;
    
    /**
     * Inizializzatore dei Servizi
     */
    public function __construct(LanguageService $languageService)
    {
        $this->languageService = $languageService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $langs = $this->languageService->getLanguageCollectionInfo(Language::all(), Gate::check('isAdmin'));
        return AppHelpers::jsonResponse(
            empty($langs) ? 'Languages successfully retrieved!' : "There are no Languages present at the moment",
            200, 
            $langs ?: [],
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(LanguageStoreRequest $request)
    {
        $lang = $this->languageService->createLanguage($request->validated());
        return AppHelpers::jsonResponse('Language Successfully Created', 201, $lang);
    }

    /**
     * Display the specified resource.
     */
    public function show(string|int $lang)
    {
        $record = AppHelpers::findByKey(Language::class, $lang, 'getLanguage');

        $langResource = $this->languageService->getLanguageInfo($record, Gate::check('isAdmin'));
        return AppHelpers::jsonResponse('Language Successfully Retrieved', 200, $langResource);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(LanguageUpdateRequest $request, int|string $lang)
    {
        $record = AppHelpers::findByKey(Language::class, $lang, 'getLanguage');

        $updatedLang = $this->languageService->updateLanguage($record, $request->validated());
        return AppHelpers::jsonResponse('Language Successfully Updated', 200, $updatedLang);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int|string $lang)
    {
        $record = AppHelpers::findByKey(Language::class, $lang, 'getLanguage');

        $this->languageService->deleteLanguage($record);
        return AppHelpers::jsonResponse('Language Successfully Deleted', 200);
    }
}