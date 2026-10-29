<?php

namespace App\Models\Location;

use Illuminate\Database\Eloquent\Model;

class ItalianProvince extends Model
{
    protected $table = 'italian_provinces';
    public $timestamps = false;

    protected $fillable = [
        'region_id',
        'capital_municipality_id',
        'name',
        'code',
    ];

    
    /* --------- RELAZIONI ---------*/

    public function region()
    {
        return $this->belongsTo(ItalianRegion::class);
    }

    public function municipalities()
    {
        return $this->hasMany(ItalianMunicipality::class);
    }

    /* --------- METODI STATICI ---------*/

    public static function getProvince(string $province) :mixed {
        return static::where('name', $province)
            ->orWhere('code', $province)
            ->first();
    }
}
