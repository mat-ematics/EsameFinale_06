<?php

namespace App\Models\Media;

use App\Models\File\File;
use App\Models\File\Fileable;
use App\Traits\FileableTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Film extends Model
{
    use SoftDeletes, FileableTrait;
    protected $table = 'films';

    protected $fillable = [
        'name',
        'description',
        'length',
        'directors',
        'actors',
        'year',
    ];

    /* --------- RELAZIONI ---------*/

    public function categories() 
    {
        return $this->belongsToMany(Category::class, 'category_film')->using(CategoryFilm::class);
    }

    public function files() : MorphToMany
    {
        return $this->morphToMany(File::class, File::getMorphName())->using(Fileable::class)->withPivot('role');
    }

    /* --------- METODI ---------*/

    public function setCategories(array $categories)
    {
        $categoryIds = Category::whereIn('name', $categories)->pluck('id')->toArray();

        $this->categories()->sync($categoryIds);
    }

    /* --------- METODI STATICI ---------*/

    public static function getAllFilms(bool $withTrashed = false) : Collection
    {
        return $withTrashed ? static::withTrashed()->get() : static::all();
    }

    public static function getFilmsWithFilters(
        ?string $name = null, ?string $category = null, ?int $year = null,
        bool $withTrashed = false, ?int $limit = null
    ): Collection {
        $query = ($withTrashed ? static::withTrashed() : static::query())
            ->with(['categories', 'files'])
            ->when($name, fn ($q, $v) => $q->where('name', 'like', '%' . addcslashes($v, '%_\\') . '%'))
            ->when($category, fn ($q, $v) => $q->whereHas('categories', fn ($c) => $c->where('name', $v)))
            ->when($year, fn ($q, $v) => $q->where('year', $v))
            ->latest()->orderByDesc('id');

        return $limit ? $query->limit($limit)->get() : $query->get();
    }

    public static function getFilm(string $film, bool $withTrashed = false) : ?self 
    {
        $query = static::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->where('name', $film)->first();
    }

    public static function findFilm(int $tvSeriesId, bool $withTrashed = false): self
    {
        $query = $withTrashed ? self::withTrashed() : self::query();
        return $query->findOrFail($tvSeriesId);
    }
}
