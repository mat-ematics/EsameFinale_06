<?php

namespace App\Models\User;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\StateEnum;
use App\Helpers\AppHelpers;
use App\Models\Authentication\Session;
use App\Models\Authorization\Role;
use App\Models\Authorization\UserRole;
use App\Models\Location\Address;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use SoftDeletes, HasFactory, Notifiable, HasApiTokens;

    protected $table = 'users';

    protected $fillable = [
        'username',
        'password',
        'salt',
        'state_id',
        'state_until',
    ];

    CONST ADMIN = 'admin';
    CONST USER = 'user';
    CONST GUEST = 'guest';


    /* --------- RELAZIONI ---------*/

    public function passwords()
    {
        return $this->hasMany(Password::class);
    }

    public function currentPassword()
    {
        return $this->hasOne(Password::class)->latestOfMany();
    }

    public function oldestPassword()
    {
        return $this->hasOne(Password::class)->oldestOfMany();
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role')->using(UserRole::class);
    }

    public function userProfile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function session()
    {
        return $this->hasOne(Session::class);
    }

    public function address()
    {
        return $this->hasOne(Address::class);
    }

    public function credit()
    {
        return $this->hasOne(Credit::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    /* --------- METODI ---------*/

    public function hasRole(string $role) : bool
    {
        return $this->roles()
            ->where('name', $role)
            ->orWhere('label', $role)
            ->exists();
    }

    public function isAdmin() : bool
    {
        return $this->hasRole(static::ADMIN);
    }

    public function giveRole(string $role)
    {
        $role = Role::where('name', $role)
            ->orWhere('label', $role)
            ->first();

        $this->roles()->syncWithoutDetaching($role->id);
    }

    public function removeRole(string $role)
    {
        $role = Role::where('name', $role)
            ->orWhere('label', $role)
            ->first();

        $this->roles()->detach($role->id);
    }

    public function getState() : StateEnum
    {
        return StateEnum::from($this->state->name);
    }

    public function setState(StateEnum $state, Carbon|int|null $untilTimestamp = null)
    {
        $stateId = State::where('name', $state->value)->value('id');

        $this->forceFill([
            'state_id' => $stateId,
            'state_until' => $untilTimestamp ? Carbon::parse($untilTimestamp) : null,
        ])->save();

        $this->refresh();
    }

    public function isActive() : bool
    {
        return $this->state->name === StateEnum::Active->value;
    }

    public function getOrCreateCredit() : Credit
    {
        return $this->credit ?? $this->credit()->create(['value' => 0]);
    }

    /* --------- METODI STATICI ---------*/

    public static function getUser(int $id, bool $withTrashed = false) : ?self 
    {
        return $withTrashed ? static::withTrashed()->find($id) : static::all()->find($id);
    }

    public static function getUserByUsername(string $username) : ?self
    {
        return static::where('username', $username)->first();
    }

    public static function getUserByUsernameHash(string $username) : ?self
    {
        $userHash = static::getUsernameHash($username);
        return static::where('username', $userHash)->first();
    }

    public static function getUsernameHash(?string $username) : ?string
    {
        return $username ? hash('sha256', $username) : null;
    }
}
