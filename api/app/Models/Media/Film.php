<?php

namespace App\Models\Media;

use App\Models\File\File;
use App\Models\File\Fileable;
use App\Traits\Traits\FileableTrait;
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
        return $this->morphToMany(File::class, File::getMorphName())->using(Fileable::class);
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
            $query->where('year', $year);
        }

        return $query->get();
    }

    public static function getFilm(string $film, bool $withTrashed = false) : ?self 
    {
        $query = static::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->where('name', $film)->first();
    }
}
