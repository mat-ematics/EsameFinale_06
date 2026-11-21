<?php
namespace App\Services\Media;

use App\Models\Media\Episode;
use App\Models\Media\Film;
use App\Models\Media\TvSeries;
use App\Services\File\MediaFileService;
use Illuminate\Database\Eloquent\Model;

class MediaPresenter
{
    public function __construct(private MediaFileService $files) {}

    public function media(Model $media, bool $isAdmin = false): array
    {
        return match (true) {
            $media instanceof Film     => $this->film($media, $isAdmin),
            $media instanceof TvSeries => $this->series($media, $isAdmin),
            $media instanceof Episode  => $this->episode($media, $isAdmin),
        };
    }

    public function collection(iterable $items, bool $isAdmin = false): array
    {
        return collect($items)->map(fn (Model $m) => $this->media($m, $isAdmin))->values()->all();
    }

    private static function minutes(?string $length): ?int
    {
        if (!$length) return null;
        [$hours, $minutes] = array_pad(explode(':', $length), 2, 0);
        return (int) $hours * 60 + (int) $minutes;
    }

    private function common(Model $m, bool $isAdmin): array
    {
        $urls = $this->files->urls($m);

        return [
            'id'          => $m->id,
            'name'        => $m->name,
            'description' => $m->description,
            'cover_url'   => $urls['cover'] ?? null,
            'banner_url'  => $urls['banner'] ?? null,
            'video_url'   => $urls['video'] ?? null,
            'deleted_at'  => $isAdmin ? $m->deleted_at : null,
        ];
    }

    private function film(Film $film, bool $isAdmin): array
    {
        $film->loadMissing('categories');

        return array_merge($this->common($film, $isAdmin), [
            'type'             => 'film',
            'year'             => $film->year,
            'length'           => $film->length,
            'duration_minutes' => self::minutes($film->length),
            'directors'        => $film->directors,
            'actors'           => $film->actors,
            'categories'       => $film->categories->pluck('name')->all(),
            'category_labels'  => $film->categories->map(fn ($c) => $c->label ?: $c->name)->all(),
        ]);
    }

    private function series(TvSeries $series, bool $isAdmin): array
    {
        $series->loadMissing('categories');

        return array_merge($this->common($series, $isAdmin), [
            'type'          => 'tv-series',
            'year'          => $series->start_year,
            'end_year'      => $series->end_year,
            'season_count'  => $series->season_count,
            'episode_count' => $series->episode_count,
            'directors'     => $series->directors,
            'actors'        => $series->actors,
            'categories'    => $series->categories->pluck('name')->all(),
            'category_labels' => $series->categories->map(fn ($c) => $c->label ?: $c->name)->all(),
        ]);
    }

    private function episode(Episode $episode, bool $isAdmin): array
    {
        return array_merge($this->common($episode, $isAdmin), [
            'type'             => 'episode',
            'tv_series_id'     => $episode->tv_series_id,
            'season_number'    => $episode->season_number,
            'episode_number'   => $episode->episode_number,
            'length'           => $episode->length,
            'duration_minutes' => self::minutes($episode->length),
            'year'             => $episode->year,
        ]);
    }
}