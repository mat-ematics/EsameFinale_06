<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserProfile extends Model
{
    /** @use HasFactory<\Database\Factories\UsernameFactory> */
    use SoftDeletes, HasFactory;

    protected $table = 'user_profiles';

    protected $fillable = [
        'user_id',
        'name',
        'surname',
        'email',
        'birthdate',
        'gender',
    ];

    /* --------- RELAZIONI ---------*/

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /* --------- METODI STATICI ---------*/

    public static function getProfileByUserId(int $userId) : ?self
    {
        return static::where('user_id', $userId)->first();
    }
}
