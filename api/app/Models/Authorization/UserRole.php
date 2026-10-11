<?php

namespace App\Models\Authorization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class UserRole extends Pivot
{
    protected $table = 'user_role';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'role_id',
    ];
}
