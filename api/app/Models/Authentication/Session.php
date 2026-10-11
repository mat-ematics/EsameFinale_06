<?php

namespace App\Models\Authentication;

use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $table = 'sessions';
    protected $primaryKey = 'jti';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $dates = [
        'issued_at',
        'expires_at',
        'revoked_at',
    ];

    protected $fillable = [
        'jti',
        'user_id',
        'issued_at',
        'expires_at',
        'revoked_at',
    ];

    /* --------- RELAZIONI ---------*/

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /* --------- METODI STATICI ---------*/

    public static function getSession(string $jti) : ?self 
    {
        return Session::where('jti', $jti)->first();
    }

    public static function startSession(int $userId, string $jti, Carbon $issuedAt, Carbon $expiresAt): ?self 
    {
        return Session::create([
            'jti' => $jti,
            'user_id' => $userId,
            'issued_at' => $issuedAt,
            'expires_at' => $expiresAt,
        ]);
    }

    public static function deleteSession(int $userId) : bool
    {
        return static::where('user_id', $userId)->delete() > 0;
    }
}
