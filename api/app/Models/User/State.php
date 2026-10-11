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
}
