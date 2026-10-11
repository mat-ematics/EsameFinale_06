<?php

namespace App\Models\Media;

use App\Models\File\File;
use App\Models\File\Fileable;
use App\Traits\Traits\FileableTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TvSeries extends Model
{
    use SoftDeletes, FileableTrait;
    
    protected $table = 'tv_series';

    protected $fillable = [
        'name',
        'description',
        'directors',
        'actors',
        'start_year',
        'end_year',
    ];

    /* --------- RELAZIONI ---------*/

    public function categories() : BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_tv_series')->using(CategoryTvSeries::class);
    }

    public function episodes() : HasMany
    {
        return $this->hasMany(Episode::class);
    }

    public function files() : MorphToMany
    {
        return $this->morphToMany(File::class, File::getMorphName())->using(Fileable::class);
    }

    /* --------- METODI ---------*/

    public function recalculateCounts()
    {
        $this->episode_count = $this->episodes->count();
        $this->season_count = $this->episodes->max('season_number') ?? 0;
        $this->save();
    }

    public function setCategories(array $categories)
    {
        $categoryIds = Category::whereIn('name', $categories)->pluck('id')->toArray();

        $this->categories()->sync($categoryIds);
    }

    public function getEpisodes(bool $withTrashed)
    {
        return $withTrashed ? $this->episodes()->withTrashed()->get() : $this->episodes;
    }

    public function getEpisodesWithFilters(?int $season, bool $withTrashed = false) 
    {
        $query = $withTrashed ? static::withTrashed() : static::query();

        if (!empty($season)) {
            $query->whereHas('episodes', function ($q) use ($season) {
                $q->where('season_number', $season);
            });
        }

        return $query->get();
    }

    /* --------- METODI STATICI ---------*/

    public static function getAllTvSeries(bool $withTrashed = false) : Collection
    {
        return $withTrashed ? static::withTrashed()->get() : static::all();
    }

    public static function getTvSeriesWithFilters(
        ?string $name = null,
        ?string $category = null,
        ?int $year = null,
        bool $withTrashed = false
    ) : Collection {
        $query = $withTrashed ? static::withTrashed() : static::query();

        if (!empty($name)) {
            $query->where('title', 'like', '%' . $name . '%');
        }

        if (!empty($category)) {
            $query->whereHas('categories', function ($q) use ($category) {
                $q->where('name', 'like', '%' . $category . '%');
            });
        }

        if (!empty($year)) {
            $query->where('start_year', '<=', $year)
                  ->where('end_year', '>=', $year);
        }

        return $query->get();
    }

    public static function getTvSeries(string $tvSeries, bool $withTrashed = false) : ?self 
    {
        $query = static::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->where('name', $tvSeries)->first();
    }
}
