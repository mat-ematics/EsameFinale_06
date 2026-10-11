<?php

namespace App\Models\File;

use Illuminate\Database\Eloquent\Relations\MorphPivot;

class Fileable extends MorphPivot
{
    protected $table = 'fileables';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'file_id',
        'fileable_id',
        'fileable_type',
    ];
}
