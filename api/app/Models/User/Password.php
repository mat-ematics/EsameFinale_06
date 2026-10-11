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
        $pswHashSimple = Password::getPasswordHash($password);
        $combined = hash('sha256', $this->salt . $pswHashSimple);
        return hash_equals($combined, $this->password);
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
        return $password ? hash("sha256", $salt . $password) : null;
    }
}