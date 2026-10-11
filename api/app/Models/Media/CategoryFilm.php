<?php

namespace App\Models\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CategoryFilm extends Pivot
{
    protected $table = 'category_film';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'category_id',
        'film_id',
    ];
}
