<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    protected $table = 'credits';

    protected $fillable = [
        'user_id',
        'value',
    ];

    /* --------- RELAZIONI --------*/

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
