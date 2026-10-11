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
}
