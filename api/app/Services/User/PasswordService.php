<?php

namespace App\Services\User;

use App\Helpers\AppHelpers;
use App\Models\User\Password;
use App\Models\User\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PasswordService {

    public function createPassword(User $user, string $password, ?string $salt = null) : Password
    {
        return DB::transaction(function () use ($user, $password, $salt) {
            
            //Creazione Hash e Salt di Password
            $pswSalt = $salt ?? AppHelpers::generateSalt();
            $pswHashSimple = Password::getPasswordHash($password);
            $pswHash = Password::getPasswordHash($pswHashSimple, $pswSalt);

            //Creazione e Inserimento Model e Record Password associato all'utente
            $pswModel = $user->passwords()->create([
                'password' => $pswHash,
                'salt' => $pswSalt,
            ]);

            return $pswModel;
        });
    }

    public function updatePassword(User $user, string $password, ?string $salt = null) : Password
    {
        $pswSalt = $salt ?? AppHelpers::generateSalt();
        $pswHashSimple = Password::getPasswordHash($password);
        $pswHash = Password::getPasswordHash($pswHashSimple, $pswSalt);

        $oldPsws = $user->passwords;

        foreach ($oldPsws as $oldPassword) {
            if ($oldPassword->checkPassword($password)) {
                throw new HttpException(400, "The new password must be different from the previous 5 ones");
            }
        }

        if ($oldPsws->count() === 5) {
            $user->oldestPassword()->delete();
        }

        return DB::transaction(function () use ($user, $pswHash, $pswSalt) {

            //Creazione e Inserimento Model e Record Password associato all'utente
            $pswModel = $user->passwords()->create([
                'password' => $pswHash,
                'salt' => $pswSalt,
            ]);

            return $pswModel;
        });
    }
}