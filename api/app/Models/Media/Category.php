<?php

namespace App\Models\Media;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';

    protected $fillable = [
        'name',
        'label',
        'description'
    ];

    /* --------- RELAZIONI ---------*/

    public function films()
    {
        return $this->belongsToMany(Film::class, 'category_film')->using(CategoryFilm::class);
    }

    public function tvSeries()
    {
        return $this->belongsToMany(TvSeries::class, 'category_tv_series')->using(CategoryTvSeries::class);
    }

    /* --------- METODI STATICI ---------*/

    public static function getAllCategories()
    {
        return static::all();
    }

    public static function getAllCategoryNames()
    {
        return static::all()->pluck('name');
    }

    public static function getCategory(string $category) : ?self 
    {
        return static::where('name', $category)
            ->orWhere('label', $category)
            ->first();
    }
}
