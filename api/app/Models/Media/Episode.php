<?php

namespace App\Models\Media;

use App\Models\File\File;
use App\Models\File\Fileable;
use App\Traits\Traits\FileableTrait;
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
        static::created(function ($episode) {
            self::recalculateCounts($episode->tv_series_id);
        });

        static::updated(function ($episode) {
            self::recalculateCounts($episode->tv_series_id);
        });

        static::deleted(function ($episode) {
            self::recalculateCounts($episode->tv_series_id);
        });

        static::restored(function ($episode) {
            self::recalculateCounts($episode->tv_series_id);
        });
    }

    protected static function recalculateCounts(int $tvSeriesId)
    {
        $tvSeries = TvSeries::with('episodes')->find($tvSeriesId);

        $tvSeries->recalculateCounts();
    }

    /* --------- RELAZIONI ---------*/

    public function tv_series() : BelongsTo
    {
        return $this->belongsTo(TvSeries::class);
    }

    public function files() : MorphToMany
    {
        return $this->morphToMany(File::class, File::getMorphName())->using(Fileable::class);
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
}
