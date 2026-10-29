<?php

namespace App\Models\Location;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = 'countries';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'continent',
        'iso',
        'iso3',
        'phone_prefix',
    ];

    /* --------- RELAZIONI ---------*/

    public function continent()
    {
        return $this->belongsTo(Continent::class);
    }

    /* --------- METODI STATICI ---------*/

    public static function getCountry(string $country) :mixed {
        return static::where('name', $country)
            ->orWhere('iso', $country)
            ->orWhere('iso3', $country)
            ->first();
    }
}
