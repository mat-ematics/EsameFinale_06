<?php

namespace App\Models\User;

use App\Helpers\AppHelpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Password extends Model
{
    protected $table = 'passwords';

    protected $fillable = [
        'user_id',
        'password',
        'salt',
    ];

    /* --------- RELAZIONI ---------*/

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /* --------- METODI ---------*/

    public function checkPassword(#[\SensitiveParameter] string $password) : bool
    {
       return hash_equals($this->password, Password::getPasswordHashDouble($password, $this->salt));
    }

    public function checkPasswordLogin(string $passwordHash) : bool {
        // return $this->password === $passwordHash;
        return hash_equals($this->password, $passwordHash);
    }

    /* --------- METODI STATICI ---------*/
    public static function getPasswordHash(
        #[\SensitiveParameter] ?string $password,
        string $salt = '',
    ) : string
    {
        if ($password === null || $password === '') {
            throw new \InvalidArgumentException('Password cannot be empty');
        }
        return $password ? hash("sha256", $salt . $password) : null;
    }

    public static function getPasswordHashDouble(
        #[\SensitiveParameter] ?string $password,
        string $salt = '',
    ) : string
    {
        if ($password === null || $password === '') {
            throw new \InvalidArgumentException('Password cannot be empty');
        }
        return static::getPasswordHash(static::getPasswordHash($password), $salt);
    }
}