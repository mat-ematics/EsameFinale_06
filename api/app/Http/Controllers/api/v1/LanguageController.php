<?php

namespace App\Http\Controllers\api\v1;

use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Language\LanguageStoreRequest;
use App\Http\Requests\Language\LanguageUpdateRequest;
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
        return AppHelpers::jsonResponse('Languages successfully retrieved!', 200, $langs);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(LanguageStoreRequest $request)
    {
        if (Gate::allows('isAdmin')) {
            try {
                $lang = $this->languageService->createLanguage($request->validated());
                return AppHelpers::jsonResponse('Language Successfully Created', 201, $lang);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }

        return AppHelpers::jsonResponseForbidden();
    }

    /**
     * Display the specified resource.
     */
    public function show(string|int $lang)
    {
        if (ctype_digit((string) $lang)) {
            $langRecord = Language::find((int) $lang);
        } else {
            $langRecord = Language::getLanguage($lang);
        }
        if (!$langRecord) {
            return AppHelpers::jsonResponse('Language Not Found', 404);
        }

        $langResource = $this->languageService->getLanguageInfo($langRecord, Gate::check('isAdmin'));
        return AppHelpers::jsonResponse('Language Successfully Retrieved', 200, $langResource);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(LanguageUpdateRequest $request, int|string $lang)
    {
        if (Gate::allows('isAdmin')) {

            $data = $request->validated();
            
            try {
                //Ricerca su ID/Nome o Label
                if (ctype_digit((string) $lang)) {
                    $langRecord = Language::find((int) $lang);
                } else {
                    $langRecord = Language::getLanguage($lang);
                }

                if (!$langRecord) {
                    throw new HttpException(404, 'Language Not Found');
                }

                $updatedLang = $this->languageService->updateLanguage($langRecord, $data);
                return AppHelpers::jsonResponse('Language Successfully Updated', 200, $updatedLang);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }

        return AppHelpers::jsonResponseForbidden();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int|string $lang)
    {
        if (Gate::allows('isAdmin')) {
            try {
                 //Ricerca su ID/Nome o Label
                if (ctype_digit((string) $lang)) {
                    $langRecord = Language::find((int) $lang);
                } else {
                    $langRecord = Language::getLanguage($lang);
                }
                
                if (!$langRecord) {
                    throw new HttpException(404, 'Language Not Found');
                }

                $this->languageService->deleteLanguage($langRecord);
                return AppHelpers::jsonResponse('Language Successfully Deleted', 204);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }

        return AppHelpers::jsonResponseForbidden();
    }
}
