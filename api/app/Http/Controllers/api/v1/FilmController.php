<?php

namespace App\Http\Controllers\api\v1;

use App\Enums\FileRoleEnum;
use App\Helpers\AppHelpers;
use App\Helpers\MediaFileRulesHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Film\FilmStoreRequest;
use App\Http\Requests\Film\FilmUpdateRequest;
use App\Http\Resources\Film\FilmResource;
use App\Models\Media\Film;
use App\Services\Media\FilmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FilmController extends Controller
{
    protected FilmService $filmService;
    
    /**
     * Inizializzatore dei Servizi
     */
    public function __construct(FilmService $filmService)
    {
        $this->filmService = $filmService;
    }


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $isAdmin = Gate::check('isAdmin');        
        $name = $request->query('name');
        $category = $request->query('category');
        $year = $request->query('year');

        $filmRecords = Film::getFilmsWithFilters($name, $category, (int) $year, $isAdmin);
        $films = $this->filmService->getFilmCollectionInfo($filmRecords, $isAdmin);

        if (empty($films))
        {
            $films = 'There are no Films Present at the moment';
        }
        return AppHelpers::jsonResponse('Films Successfully Retrieved', 200, $films);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FilmStoreRequest $request)
    {
        if (Gate::allows('isAdmin')) {
            try {
                $uploadedFiles = collect($request->file('files'));
                $film = $this->filmService->createFilm($request->validated(), $uploadedFiles);
                return AppHelpers::jsonResponse('Film Successfully Created', 201, $film);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }

        return AppHelpers::jsonResponseForbidden();
    }

    /**
     * Display the specified resource.
     */
    public function show(int $filmId)
    {
        $isAdmin = Gate::check('isAdmin');
        $filmRecord = $isAdmin ? Film::withTrashed()->find($filmId) : Film::find($filmId);

        if (!$filmRecord) {
            return AppHelpers::jsonResponse('Film Not Found', 404);
        }

        $filmResource = $this->filmService->getFilmInfo($filmRecord, $isAdmin);
        return AppHelpers::jsonResponse('Film Successfully Retrieved', 200, $filmResource);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FilmUpdateRequest $request, int $filmId)
    {
        if (Gate::allows('isAdmin')) {

            $data = $request->validated();

            try {
                //Ricerca su ID/Nome o Label
                $filmRecord = Film::withTrashed()->find($filmId);

                if (!$filmRecord) {
                    throw new HttpException(404, 'Film Not Found');
                }

                MediaFileRulesHelper::checkRoleValidity($filmRecord, $data['files_meta']);

                $labels = AppHelpers::extractColumnFromArray($data['files_meta'], 'label');
                $files = $filmRecord->getManyFilesByLabels($labels);

                $updatedFilm = $this->filmService->updateFilm($filmRecord, $data, $files);

                return AppHelpers::jsonResponse('Film Successfully Updated', 200, $updatedFilm);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }

        return AppHelpers::jsonResponseForbidden();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $filmId)
    {
        if (Gate::allows('isAdmin')) {
            try {
                 //Ricerca su ID/Nome o Label
                $filmRecord = Film::withTrashed()->find($filmId);
                
                if (!$filmRecord) {
                    throw new HttpException(404, 'Film Not Found');
                }

                if ($filmRecord->trashed()) {
                    throw new HttpException(409, 'The Selected Film is already Deleted');
                }

                $this->filmService->deleteFilm($filmRecord);
                return AppHelpers::jsonResponse('Film Successfully Deleted', 204);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }

        return AppHelpers::jsonResponseForbidden();
    }

    public function restore(int $filmId)
    {
        if (Gate::allows('isAdmin')) {
            try {
                //Ricerca su ID/Nome o Label
                $filmRecord = Film::withTrashed()->find($filmId);

                if (!$filmRecord) {
                    throw new HttpException(404, 'Film Not Found');
                }

                if (!$filmRecord->trashed()) {
                    throw new HttpException(409, 'Film is Not Deleted');
                }

                $filmResource = $this->filmService->restorefilm($filmRecord);
                return AppHelpers::jsonResponse('Film Successfully Restored', 200, $filmResource);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }

        return AppHelpers::jsonResponseForbidden();
    }

    public function forceDestroy(int $filmId)
    {
        if (Gate::allows('isAdmin')) {
            try {
                //Ricerca su ID/Nome o Label
                $filmRecord = Film::withTrashed()->find($filmId);

                if (!$filmRecord) {
                    throw new HttpException(404, 'Film Not Found');
                }

                if (!$filmRecord->trashed()) {
                    throw new HttpException(409, 'Film is Not (Soft) Deleted');
                }

                $this->filmService->forceDeleteFilm($filmRecord);
                return AppHelpers::jsonResponse('Film has been Permanently Deleted', 204);
            } catch (\Throwable $th) {
                return AppHelpers::jsonResponse($th->getMessage(), AppHelpers::safeHttpStatus($th));
            }
        }

        return AppHelpers::jsonResponseForbidden();
    }
}
