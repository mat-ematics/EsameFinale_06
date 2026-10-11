<?php

namespace App\Models\Authentication;

use App\Helpers\AppHelpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Access extends Model
{
    use SoftDeletes, HasFactory;
    
    protected $table = 'accesses';

    protected $fillable = [
        'ip',
        'user_hash',
        'attempts',
        'attempt_start_at',
        'last_attempt_at',
    ];

    const ATTEMPT_DURATION = 60;
    const MAX_ATTEMPTS = 5;
    const ATTEMPT_LOCK_DURATION = 300;

    /* --------- HELPER ---------*/

    protected function withDefault($value, $default)
    {
        return $value ?? $default;
    }

    /* --------- METODI ---------*/

    public function addError() : bool
    {
        return $this->update([
            'attempts' => DB::raw('attempts + 1'),
            'last_attempt_at' => now(),
        ]);
    }

    public function resetAttempts() : bool
    {
        return $this->updateOrFail([
            'attempts' => 1,
        ]);
    }

    public function maxAttemptsReached(?int $maxAttempts = null) : bool
    {
        return $this->attempts > $this->withDefault($maxAttempts, static::MAX_ATTEMPTS);
    }

    public function isLocked(null|int|string $loginLockDuration = null) : bool
    {
        return AppHelpers::isInFutureAfter(
            $this->last_attempt_at,
            $this->withDefault($loginLockDuration, static::ATTEMPT_LOCK_DURATION)
        );
    }

    public function hasActiveAttempt(null|int|string $attemptDuration = null) : bool
    {
        return !AppHelpers::isInFutureAfter(
            $this->attempt_start_at,
            $this->withDefault($attemptDuration, static::ATTEMPT_DURATION)
        );;
    }

    /* --------- METODI STATICI ---------*/

    public static function getAccessByIp(string $ip) : ?self
    {
        return static::where('ip', $ip)->first();
    }

    public static function getAccess(string $ip, string $userHash) : ?self
    {
        return static::where('ip', $ip)->where('user_hash', $userHash)->first();
    }

    public static function createAccess(string $ip, string $userHash) : ?self
    {
        return static::create([
            'ip' => $ip,
            'user_hash' => $userHash,
            'attempts' => 1,
        ]);
    }

    public static function startAttempt(string $ip, string $userHash): self
    {
        $access = static::firstOrNew(['user_hash' => $userHash, 'ip' => $ip]);

        if (!$access->exists) {
            $access->attempts = 1; // only initialize on first creation
        }

        $access->attempt_start_at = now();
        $access->save();

        return $access;
    }

    public static function deleteAccess(string $ip) : bool
    {
        return static::where('ip', $ip)->delete();
    }
}
