<?php

namespace App\Services\User;

use App\Helpers\AppHelpers;
use App\Http\Resources\User\ProfileResource;
use App\Models\User\User;
use Illuminate\Support\Arr;

class ProfileService {
    
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


    public function getProfile(User $authUser) : ProfileResource
    {
        return new ProfileResource($authUser);
    }

    public function updateProfile(User $authUser, array $data) : ProfileResource
    {
        $data = array_filter($data);

        //Update di Utente e Password
        $authUser->update(array_filter([ //Filtra i valori nulli
            'username' => User::getUsernameHash($data['username']),
        ]));

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

        return $this->getProfile($authUser);
    }

    public function deleteProfile(User $authUser)
    {
        $authUser->deleteOrFail();
    }

    public function getCreditValue(User $user)
    {
        return $user->getOrCreateCredit()->value;
    }

    public function addCredit(User $user, float $creditToAdd)
    {
        $credit = $user->getOrCreateCredit();

        $credit->value += $creditToAdd;
        $credit->save();
        $credit->refresh();

        return $credit->value;
    }

    public function removeCredit(User $user, float $creditToSub)
    {
        $credit = $user->getOrCreateCredit();

        $credit->value -= $creditToSub;
        $credit->save();
        $credit->refresh();

        return $credit->value;
    }
}