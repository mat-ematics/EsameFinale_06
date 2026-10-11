<?php

namespace App\Models\Location;

use App\Models\User\User;
use Faker\Provider\sv_SE\Municipality;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $table = 'addresses';

    protected $fillable = [
        'user_id',
        'country_id',
        'italian_municipality_id',
        'cap',
        'street_address',
        'house_number',
        'locality',
        'additional_info',
    ];

    
    /* --------- RELAZIONI ---------*/

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function italianMunicipality()
    {
        return $this->belongsTo(Municipality::class);
    }
}
