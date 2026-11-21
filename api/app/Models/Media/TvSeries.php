<?php

namespace App\Models\Media;

use App\Models\File\File;
use App\Models\File\Fileable;
use App\Traits\FileableTrait;
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
        return $this->morphToMany(File::class, File::getMorphName())->using(Fileable::class)->withPivot('role');;
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

    public function getEpisodesWithFilters(?int $season = null, bool $withTrashed = false): Collection
    {
        $query = $withTrashed ? $this->episodes()->withTrashed() : $this->episodes();

        return $query->with('files')
            ->when($season, fn ($q, $s) => $q->where('season_number', $s))
            ->orderBy('season_number')->orderBy('episode_number')
            ->get();
    }

    /* --------- METODI STATICI ---------*/

    public static function getAllTvSeries(bool $withTrashed = false) : Collection
    {
        return $withTrashed ? static::withTrashed()->get() : static::all();
    }

    public static function getTvSeriesWithFilters(
        ?string $name = null, ?string $category = null, ?int $year = null,
        bool $withTrashed = false, ?int $limit = null
    ): Collection {
        $query = ($withTrashed ? static::withTrashed() : static::query())
            ->with(['categories', 'files'])
            ->when($name, fn ($q, $v) => $q->where('name', 'like', '%' . addcslashes($v, '%_\\') . '%'))
            ->when($category, fn ($q, $v) => $q->whereHas('categories', fn ($c) => $c->where('name', $v)))
            ->when($year, fn ($q, $v) => $q->where('start_year', '<=', $v)
                ->where(fn ($e) => $e->whereNull('end_year')->orWhere('end_year', '>=', $v)))
            ->latest()->orderByDesc('id');

        return $limit ? $query->limit($limit)->get() : $query->get();
    }

    public static function getTvSeries(string $tvSeries, bool $withTrashed = false) : ?self 
    {
        $query = static::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->where('name', $tvSeries)->first();
    }

    public static function findSeries(int $tvSeriesId, bool $withTrashed = false): self
    {
        $query = $withTrashed ? self::withTrashed() : self::query();
        return $query->findOrFail($tvSeriesId);
    }
}
