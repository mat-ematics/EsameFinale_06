<?php

namespace App\Models\Authorization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
    use SoftDeletes, HasFactory;
    protected $table = 'permissions';

    protected $fillable = [
        'name',
        'label',
    ];

    /* --------- RELAZIONI ---------*/

    public function roles()
    {
        return $this->belongsToMany(Role::class)->using(RolePermission::class);
    }
}
