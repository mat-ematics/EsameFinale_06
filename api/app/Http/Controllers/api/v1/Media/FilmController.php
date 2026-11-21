<?php

namespace App\Http\Controllers\api\v1\Media;

use App\Helpers\AppHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\Film\FilmStoreRequest;
use App\Models\Media\Film;
use App\Services\Media\FilmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FilmController extends Controller
{
    public function __construct(protected FilmService $filmService) {}

    public function index(Request $request)
    {
        $isAdmin = Gate::check('isAdmin');

        $records = Film::getFilmsWithFilters(
            $request->query('name'), $request->query('category'), (int) $request->query('year'),
            $isAdmin, $this->limit($request),
        );
        $films = $this->filmService->getFilmCollectionInfo($records, $isAdmin);

        return AppHelpers::jsonResponse(
            $films ? 'Films Successfully Retrieved' : 'There are no Films present at the moment', 200, $films
        );
    }

    public function show(int $filmId)
    {
        $isAdmin = Gate::check('isAdmin');
        $film = Film::findFilm($filmId, $isAdmin);

        return AppHelpers::jsonResponse('Film Successfully Retrieved', 200, $this->filmService->getFilmInfo($film, $isAdmin));
    }

    public function store(FilmStoreRequest $request)
    {
        $film = $this->filmService->createFilm($request->validated(), $this->uploads($request));
        return AppHelpers::jsonResponse('Film Successfully Created', 201, $film);
    }

    public function update(FilmStoreRequest $request, int $filmId)
    {
        $film = Film::findFilm($filmId, true);

        $updated = $this->filmService->updateFilm(
            $film, $request->validated(), $this->uploads($request), $request->boolean('remove_banner')
        );
        return AppHelpers::jsonResponse('Film Successfully Updated', 200, $updated);
    }

    public function destroy(int $filmId)
    {
        $film = Film::findFilm($filmId, true);
        abort_if($film->trashed(), 409, 'The Selected Film is already Deleted');

        $this->filmService->deleteFilm($film);
        return AppHelpers::jsonResponse('Film Successfully Deleted', 200);
    }

    public function restore(int $filmId)
    {
        $film = Film::findFilm($filmId, true);
        abort_unless($film->trashed(), 409, 'Film is Not Deleted');

        return AppHelpers::jsonResponse('Film Successfully Restored', 200, $this->filmService->restoreFilm($film));
    }

    public function forceDestroy(int $filmId)
    {
        $film = Film::findFilm($filmId, true);
        abort_unless($film->trashed(), 409, 'Film is Not (Soft) Deleted');

        $this->filmService->forceDeleteFilm($film);
        return AppHelpers::jsonResponse('Film has been Permanently Deleted', 200);
    }
}