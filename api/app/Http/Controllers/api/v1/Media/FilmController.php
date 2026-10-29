<?php

namespace App\Http\Controllers\api\v1\Media;

use App\Helpers\AppHelpers;
use App\Helpers\MediaFileRulesHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\Film\FilmStoreRequest;
use App\Http\Requests\Media\Film\FilmUpdateRequest;
use App\Models\Media\Film;
use App\Services\Media\FilmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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

        return AppHelpers::jsonResponse(
            empty($films) ? 'Films Successfully Retrieved' : 'There are no Films Present at the moment',
            200,
            $films ?: [],
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FilmStoreRequest $request)
    {
        $uploadedFiles = collect($request->file('files'))->all();
        $film = $this->filmService->createFilm($request->validated(), $uploadedFiles);

        return AppHelpers::jsonResponse('Film Successfully Created', 201, $film);

    }

    /**
     * Display the specified resource.
     */
    public function show(int $filmId)
    {
        $isAdmin = Gate::check('isAdmin');
        $filmRecord = Film::findFilm($filmId, $isAdmin);

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
        $filmRecord = Film::findFilm($filmId, Gate::check('isAdmin'));
        $data = $request->validated();
        
        MediaFileRulesHelper::checkRoleValidity($filmRecord, $data['files_meta']);
        
        $labels = AppHelpers::extractColumnFromArray($data['files_meta'], 'label');
        $files = $filmRecord->getManyFilesByLabels($labels);

        $updatedFilm = $this->filmService->updateFilm($filmRecord, $data, $files);
        return AppHelpers::jsonResponse('Film Successfully Updated', 200, $updatedFilm);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $filmId)
    {
        $record = Film::findFilm($filmId);
        abort_if($record->trashed(), 409, 'The Selected Film is already Deleted');

        $this->filmService->deleteFilm($record);
        return AppHelpers::jsonResponse('Film Successfully Deleted', 200);
    }

    public function restore(int $filmId)
    {
        $record = Film::findFilm($filmId, true);
        abort_unless($record->trashed(), 409, 'Film is Not Deleted');

        $resource = $this->filmService->restoreFilm($record);
        return AppHelpers::jsonResponse('Film Successfully Restored', 200, $resource);
    }

    public function forceDestroy(int $filmId)
    {
        $record = film::findSeries($filmId);
        abort_unless($record->trashed(), 409, 'Film is Not (Soft) Deleted');

        $this->filmService->forceDeleteFilm($record);
        return AppHelpers::jsonResponse('Film has been Permanently Deleted', 200);
    }
}
