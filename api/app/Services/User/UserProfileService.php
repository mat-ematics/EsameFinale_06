<?php

namespace App\Services\User;

use App\Helpers\AppHelpers;
use App\Http\Resources\User\User\UserProfileResource;
use App\Models\User\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UserProfileService {
    
    protected AddressService $addressService;
    protected PasswordService $passwordService;
    
    /**
     * Inizializzatore dei Servizi
     */
    public function __construct(AddressService $addressService, PasswordService $passwordService)
    {
        $this->addressService = $addressService;
        $this->passwordService = $passwordService;
    }


    public function getProfile(User $authUser) : UserProfileResource
    {
        return new UserProfileResource($authUser);
    }

    public function updateProfile(User $authUser, array $data) : UserProfileResource
    {
        $data = array_filter($data, fn ($value) => $value !== null);

        DB::transaction(function () use ($authUser, $data) {

            //Update di Utente e Password
            if (!empty($data['username'])) {
                $authUser->update(['username' => User::getUsernameHash($data['username'])]);
            }
    
            if (!empty($data['password'])) {
                $this->passwordService->updatePassword($authUser, $data['password']);
            }
    
            //Filtro e Aggiornamento delle Informazioni Profilo
            $profileData = Arr::only($data, ['name', 'surname', 'email', 'birthdate', 'gender']);
    
            //Update Profilo
            if (!empty($profileData)) {
                $authUser->userProfile->update($profileData);
            }
    
            //Update Record Indirizzo
            $this->addressService->updateAddress($authUser, $data);
        });

        return $this->getProfile($authUser);
    }

    public function deleteProfile(User $authUser)
    {
        $authUser->deleteOrFail();
    }

    public function getCredit(User $user)
    {
        return $user->getOrCreateCredit()->count;
    }

    public function addCredit(User $user, float $creditToAdd)
    {
        $credit = $user->getOrCreateCredit();

        $credit->count += $creditToAdd;
        $credit->save();
        $credit->refresh();

        return $credit->count;
    }

    public function removeCredit(User $user, float $creditToSub)
    {
        $credit = $user->getOrCreateCredit();

        $credit->count -= $creditToSub;
        $credit->save();
        $credit->refresh();

        return $credit->count;
    }
}