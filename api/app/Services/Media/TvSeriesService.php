<?php

namespace App\Services\Media;

use App\Enums\MediaRoleEnum;
use App\Models\Media\Episode;
use App\Models\Media\TvSeries;
use App\Services\File\MediaFileService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class TvSeriesService
{
    private const FIELDS = ['name', 'description', 'directors', 'actors', 'start_year', 'end_year'];

    public function __construct(protected MediaFileService $files, protected MediaPresenter $presenter) {}

    public function getTvSeriesInfo(TvSeries $series, bool $isAdmin = false): array
    {
        return $this->presenter->media($series, $isAdmin);
    }

    public function getTvSeriesCollectionInfo(iterable $series, bool $isAdmin = false): array
    {
        return $this->presenter->collection($series, $isAdmin);
    }

    public function createTvSeries(array $data, array $uploads): array
    {
        $series = null;

        try {
            return DB::transaction(function () use ($data, $uploads, &$series) {
                $series = TvSeries::create(Arr::only($data, self::FIELDS));
                $series->setCategories($data['categories']);
                $this->files->storeMany($series, $uploads);

                return $this->presenter->media($series->fresh(['categories', 'files']), true);
            });
        } catch (\Throwable $e) {
            if ($series) $this->files->purge($this->files->directory($series));
            throw $e;
        }
    }

    public function updateTvSeries(TvSeries $series, array $data, array $uploads, bool $removeBanner = false): array
    {
        return DB::transaction(function () use ($series, $data, $uploads, $removeBanner) {
            $series->update(Arr::only($data, self::FIELDS));    // end_year => null means "still running"

            if (array_key_exists('categories', $data)) $series->setCategories($data['categories']);

            $this->files->storeMany($series, $uploads);
            if ($removeBanner) $this->files->remove($series, MediaRoleEnum::Banner);

            return $this->presenter->media($series->fresh(['categories', 'files']), true);
        });
    }

    public function deleteTvSeries(TvSeries $series): void
    {
        $series->deleteOrFail();
    }

    public function restoreTvSeries(TvSeries $series): array
    {
        $series->restore();
        return $this->presenter->media($series, true);
    }

    public function forceDeleteTvSeries(TvSeries $series): void
    {
        DB::transaction(function () use ($series) {
            // The episode rows go with the series (ON DELETE CASCADE), their files do not
            $series->episodes()->withTrashed()->get()->each(fn (Episode $e) => $this->files->deleteAll($e));

            $series->forceDelete();
            $this->files->deleteAll($series);
        });
    }
}