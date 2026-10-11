<?php

namespace App\Models\Authorization;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use SoftDeletes, HasFactory;
    protected $table = 'roles';

    protected $fillable = [
        'name',
        'label',
    ];

    /* --------- RELAZIONI ---------*/

    public function permissions()
    {
        return $this->belongsToMany(Permission::class)->using(RolePermission::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_role')->using(UserRole::class);
    }
    
    public static function getRole(string $role) : ?self
    {
        return static::where('name', $role)
            ->orWhere('label', $role)
            ->first();
    }

    public static function getRoleId(string $role) : ?int
    {
        return static::getRole($role)->id;
    }

    /* --------- METODI ---------*/

    public function getRoles() : \Illuminate\Database\Eloquent\Collection
    {
        return static::all(['name', 'label']);
    }
}
