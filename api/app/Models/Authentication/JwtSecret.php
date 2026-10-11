<?php

namespace App\Models\Authentication;

use App\Helpers\AppHelpers;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JwtSecret extends Model
{
    use HasFactory;
    protected $table = 'jwt_secrets';

    protected $fillable = [
        'user_id',
        'secret',
    ];

    /* --------- RELAZIONI ---------*/

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /* --------- METODI STATICI ---------*/

    public static function generateSecret(int $userId) : ?string
    {
        $record = static::updateOrCreate(
            ['user_id' => $userId],
            ['secret' => AppHelpers::generateSalt()]
        );

        return $record->secret;
    }

    public static function revokeSecret(int $userId)
    {
        return static::where('user_id', $userId)->delete() > 0;
    }

    public static function getSecret(int $userId) : ?string
    {
        $record = static::where('user_id', $userId)->first();
        return $record->secret;
    }
}
