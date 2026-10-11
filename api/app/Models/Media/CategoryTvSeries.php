<?php

namespace App\Models\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CategoryTvSeries extends Pivot
{
    protected $table = 'category_tv_series';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'category_id',
        'tv_series_id',
    ];
}
