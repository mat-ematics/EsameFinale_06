<?php 

namespace App\Services\Media;

use App\Models\Media\Episode;
use App\Services\File\MediaFileService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EpisodeService
{
    private const FIELDS = ['name', 'description', 'season_number', 'episode_number', 'length', 'year'];

    public function __construct(protected MediaFileService $files, protected MediaPresenter $presenter) {}

    public function getEpisodeInfo(Episode $episode, bool $isAdmin = false): array
    {
        return $this->presenter->media($episode, $isAdmin);
    }

    public function getEpisodeCollectionInfo(iterable $episodes, bool $isAdmin = false): array
    {
        return $this->presenter->collection($episodes, $isAdmin);
    }

    public function isEpisodeTaken(int $tvSeriesId, int $seasonNumber, int $episodeNumber, ?int $excludedId = null): bool
    {
        return Episode::withTrashed()
            ->where('tv_series_id', $tvSeriesId)
            ->where('season_number', $seasonNumber)
            ->where('episode_number', $episodeNumber)
            ->when($excludedId, fn ($q, $id) => $q->where('id', '!=', $id))
            ->exists();                         // trashed ones count: the DB unique index does too
    }

    public function createEpisode(int $tvSeriesId, array $data, array $uploads): array
    {
        $episode = null;

        try {
            return DB::transaction(function () use ($tvSeriesId, $data, $uploads, &$episode) {
                $episode = Episode::create(['tv_series_id' => $tvSeriesId] + Arr::only($data, self::FIELDS));
                $this->files->storeMany($episode, $uploads);

                return $this->presenter->media($episode->fresh('files'), true);
            });
        } catch (\Throwable $e) {
            if ($episode) $this->files->purge($this->files->directory($episode));
            throw $e;
        }
    }

    public function updateEpisode(Episode $episode, array $data, array $uploads): array
    {
        return DB::transaction(function () use ($episode, $data, $uploads) {
            $episode->update(Arr::only($data, self::FIELDS));
            $this->files->storeMany($episode, $uploads);

            return $this->presenter->media($episode->fresh('files'), true);
        });
    }

    public function deleteEpisode(Episode $episode): void
    {
        $episode->deleteOrFail();
    }

    public function restoreEpisode(Episode $episode): array
    {
        $episode->restore();
        return $this->presenter->media($episode, true);
    }

    public function forceDeleteEpisode(Episode $episode): void
    {
        DB::transaction(function () use ($episode) {
            $episode->forceDelete();
            $this->files->deleteAll($episode);
        });
    }
}