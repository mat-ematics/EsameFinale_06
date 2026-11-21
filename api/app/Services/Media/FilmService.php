<?php

namespace App\Services\Media;

use App\Enums\MediaRoleEnum;
use App\Models\Media\Film;
use App\Services\File\MediaFileService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class FilmService
{
    private const FIELDS = ['name', 'description', 'length', 'directors', 'actors', 'year'];

    public function __construct(protected MediaFileService $files, protected MediaPresenter $presenter) {}

    public function getFilmInfo(Film $film, bool $isAdmin = false): array
    {
        return $this->presenter->media($film, $isAdmin);
    }

    public function getFilmCollectionInfo(iterable $films, bool $isAdmin = false): array
    {
        return $this->presenter->collection($films, $isAdmin);
    }

    /** @param array<string, \Illuminate\Http\UploadedFile> $uploads cover, banner?, video */
    public function createFilm(array $data, array $uploads): array
    {
        $film = null;

        try {
            return DB::transaction(function () use ($data, $uploads, &$film) {
                $film = Film::create(Arr::only($data, self::FIELDS));
                $film->setCategories($data['categories']);
                $this->files->storeMany($film, $uploads);

                return $this->presenter->media($film->fresh(['categories', 'files']), true);
            });
        } catch (\Throwable $e) {
            if ($film) $this->files->purge($this->files->directory($film));   // the row rolled back: remove its folder too
            throw $e;
        }
    }

    public function updateFilm(Film $film, array $data, array $uploads, bool $removeBanner = false): array
    {
        return DB::transaction(function () use ($film, $data, $uploads, $removeBanner) {
            $film->update(Arr::only($data, self::FIELDS));      // only what was sent; description => null clears it

            if (array_key_exists('categories', $data)) $film->setCategories($data['categories']);

            $this->files->storeMany($film, $uploads);
            if ($removeBanner) $this->files->remove($film, MediaRoleEnum::Banner);

            return $this->presenter->media($film->fresh(['categories', 'files']), true);
        });
    }

    public function deleteFilm(Film $film): void
    {
        $film->deleteOrFail();
    }

    public function restoreFilm(Film $film): array
    {
        $film->restore();
        return $this->presenter->media($film, true);
    }

    public function forceDeleteFilm(Film $film): void
    {
        DB::transaction(function () use ($film) {
            $film->forceDelete();
            $this->files->deleteAll($film);
        });
    }
}