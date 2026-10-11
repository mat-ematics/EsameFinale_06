<?php

namespace App\Http\Resources\User;

use App\Http\Resources\Address\AddressResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->getPublicColumns($request);
    }

    /**
     * Return public columns
     */
    protected function getPublicColumns(Request $request) {


        return  [
            'username' => $this->username,
            'roles' => $this->roles()->get()->pluck('name'),
            'email' => $this->userProfile->email,
            'name' => $this->userProfile->name,
            'surname' => $this->userProfile->surname,
            'birthdate' => $this->userProfile->birthdate,
            'gender' => $this->userProfile->gender,
            'state' => $this->state->name,
            'state_until' => $this->state_until,

            //Informazioni Creazione, Update e Eliminazione
            'created_at' => $this->created_at,
            'last_updated' => $this->updated_at,

            //Informazioni Indirizzo
            'address' => (new AddressResource($this->address))->toArray($request),
        ];
    }
}
