<?php

namespace App\Services\User;

use App\Http\Resources\Address\AddressResource;
use App\Models\Location\Address;
use App\Models\User\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AddressService {

    /**
     * Ritorna le informazioni associate al Model Indirizzo Passato
     */
    public function getAddressInfo(Address $address) : AddressResource
    {
        return new AddressResource($address);
    }

    /**
     * Crea e ritorna il model di un Indirizzo associato a un utente
     */
    public function createAddress(User $user, array $data) : Address 
    {
        return DB::transaction(function () use ($user, $data) {
            
            //Creazione Indirizzo

            $address = Address::create([
                'user_id' => $user->id,
                'country_id' => $data['country_id'],
                'italian_municipality_id'  => $data['italian_municipality_id'],
                'cap'  => $data['cap'],
                'street_address'  => $data['street_address'],
                'house_number'  => $data['house_number'],
                'locality'  => $data['locality'],
                'additional_info'  => $data['additional_info'],
            ]);

            return $address;
        });
    }

    /**
     * Aggiorna le informazioni associate all'indirizzo dell'Utente Passato
     */
    public function updateAddress(User $user, array $data)
    {
        //Filtra i Valori Null
        $addressData = array_filter(Arr::only($data, [
            'country_id',
            'italian_municipality_id',
            'cap',
            'street_address',
            'house_number',
            'locality',
            'additional_info',
        ]));

        $user->address->update($addressData);
    }
}