<?php

namespace App\Models\Authorization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class RolePermission extends Pivot
{
    protected $table = 'role_permission';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'role_id',
        'permission_id',
    ];
}
