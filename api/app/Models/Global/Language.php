<?php

namespace App\Models\Global;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $table = 'languages';
    public $timestamps = false;

    /* --------- RELAZIONI ---------*/

    public function translations()
    {
        return $this->hasMany(Translation::class);
    }

    public function customTranslations()
    {
        return $this->hasMany(CustomTranslation::class);
    }

    /* --------- METODI STATICI ---------*/

    public static function getLanguage(string $key) :mixed {
        return static::where('key', $key)->value('value');
    }
}
