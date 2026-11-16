<?php

namespace App\Services\User;

use App\Http\Resources\User\Address\AddressResource;
use App\Models\Location\Address;
use App\Models\Location\Country;
use App\Models\Location\ItalianMunicipality;
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
                'italian_municipality_id' => $data['italian_municipality_id'] ?? null,
                'cap' => $data['cap'],
                'street_address' => $data['street_address'],
                'house_number' => $data['house_number'],
                'locality' => $data['locality'] ?? null,
                'additional_info' => $data['additional_info'] ?? null,
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

    /** What the profile page needs: the address plus the tax code, with readable names */
    public function getUserAddressData(User $user) {
        $address = $user->address;

        $country = $address?->country_id
            ? Country::find($address->country_id, ['id', 'name', 'iso'])
            : null;

        $municipality = null;
        if ($address?->italian_municipality_id) {
            $m = ItalianMunicipality::query()
                ->leftJoin('italian_provinces as p', 'p.id', '=', 'italian_municipalities.province_id')
                ->where('italian_municipalities.id', $address->italian_municipality_id)
                ->first([
                    'italian_municipalities.id', 'italian_municipalities.name', 'italian_municipalities.cap',
                    'italian_municipalities.catastal_code', 'italian_municipalities.province_id',
                    'p.region_id', 'p.code as province_code',
                ]);
            
            // Same shape as the municipality search, so the picker can show it directly
            $municipality = $m ? [
                'id'            => $m->id,
                'name'          => $m->name,
                'label'         => $m->province_code ? "{$m->name} ({$m->province_code})" : $m->name,
                'cap'           => $m->cap,
                'catastal_code' => $m->catastal_code,
                'province_id'   => $m->province_id,
                'region_id'     => $m->region_id,
            ] : null;
        }

        return [
            'country'              => $country?->only(['id', 'name', 'iso']),
            'italian_municipality' => $municipality,
            'cap'                  => $address?->cap,
            'street_address'       => $address?->street_address,
            'house_number'         => $address?->house_number,
            'locality'             => $address?->locality,
            'additional_info'      => $address?->additional_info,
        ];
    }

    /** Full replace: a missing optional field clears it (updateAddress() cannot) */
    public function replaceAddress(User $user, array $data): Address
    {
        return Address::updateOrCreate(
            ['user_id' => $user->id],
            [
                'country_id'              => $data['country_id'],
                'italian_municipality_id' => $data['italian_municipality_id'] ?? null,
                'cap'                     => $data['cap'],
                'street_address'          => $data['street_address'],
                'house_number'            => $data['house_number'],
                'locality'                => $data['locality'] ?? null,
                'additional_info'         => $data['additional_info'] ?? null,
            ]
        );
    }
    }