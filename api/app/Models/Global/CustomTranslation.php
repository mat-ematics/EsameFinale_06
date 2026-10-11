<?php

namespace App\Models\Global;

use Illuminate\Database\Eloquent\Model;

class CustomTranslation extends Model
{
    protected $table = 'custom_translations';
    public $timestamps = false;

    /* --------- RELAZIONI ---------*/

    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    /* --------- METODI STATICI ---------*/

    public static function getTranslation(string $key) :mixed {
        return static::where('key', $key)->value('value');
    }
}
