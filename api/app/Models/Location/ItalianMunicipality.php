<?php

namespace App\Models\Location;

use Illuminate\Database\Eloquent\Model;

class ItalianMunicipality extends Model
{
    protected $table = 'italian_municipalities';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'province_id',
        'cap',
        'catastal_code',
    ];

    
    /* --------- RELAZIONI ---------*/

    public function province()
    {
        return $this->belongsTo(ItalianProvince::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }
}
