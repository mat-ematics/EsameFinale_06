<?php

namespace App\Models\Media;

use App\Models\File\File;
use App\Models\File\Fileable;
use App\Traits\FileableTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Episode extends Model
{
    use SoftDeletes, FileableTrait;

    protected $table = 'episodes';

    protected $fillable = [
        'tv_series_id',
        'name',
        'description',
        'season_number',
        'episode_number',
        'length',
        'year',
    ];

    /* --------- CONFIGURAZIONI ---------*/

    protected static function booted()
    {
        foreach (['created', 'updated', 'deleted', 'restored', 'forceDeleted'] as $event) {
            static::$event(fn ($episode) => self::recalculateCounts($episode->tv_series_id));
        }
    }

    protected static function recalculateCounts(int $tvSeriesId): void
    {
        TvSeries::withTrashed()->with('episodes')->find($tvSeriesId)?->recalculateCounts();
    }

    /* --------- RELAZIONI ---------*/

    public function tv_series() : BelongsTo
    {
        return $this->belongsTo(TvSeries::class);
    }

    public function files() : MorphToMany
    {
        return $this->morphToMany(File::class, File::getMorphName())->using(Fileable::class)->withPivot('role');;
    }

    /* --------- METODI STATICI ---------*/

    public static function getAllEpisodes(bool $withTrashed = false) : Collection
    {
        return $withTrashed ? static::withTrashed()->get() : static::all();
    }

    public static function getEpisode(string $episode, bool $withTrashed = false) : ?self 
    {
        $query = static::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->where('name', $episode)->first();
    }

    /** The episode must belong to the series, otherwise "Episode Not Found" (404). */
    public static function findEpisode(int $tvSeriesId, int $episodeId, bool $withTrashed = false): Episode
    {
        TvSeries::findSeries($tvSeriesId, $withTrashed);

        $query = $withTrashed ? Episode::withTrashed() : Episode::query();
        return $query->where('tv_series_id', $tvSeriesId)->findOrFail($episodeId);
    }
}
