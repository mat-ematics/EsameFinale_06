<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    protected $table = 'states';

    protected $fillable = [
        'name',
        'label',
        'description',
    ];

    /* --------- RELAZIONI ---------*/
    
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /* --------- METODI STATICI ---------*/

    public static function getState(string $state) : ?self 
    {
        return static::where('name', $state)
            ->orWhere('label', $state)
            ->first();
    }
}
