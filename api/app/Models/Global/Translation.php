<?php

namespace App\Models\Global;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    protected $table = 'translations';
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
