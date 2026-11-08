<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    protected $table = 'credits';

    protected $fillable = [
        'user_id',
        'count',
    ];

    public const MAX_COUNT = 999999.99;

    /* --------- RELAZIONI --------*/

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
