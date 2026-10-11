<?php

namespace App\Http\Resources\Address;

use App\Models\Location\Country;
use App\Models\Location\ItalianMunicipality;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class AddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
       $data = $this->getPublicColumns($request);
       return $data;
    }

    /**
     * Return public columns
     */
    protected function getPublicColumns(Request $request) {
        $data = parent::toArray($request);

        $country = Country::find($this->country_id);
        $municipality = ItalianMunicipality::find($this->italian_municipality_id) ?? null;

        $returnData = array_filter([
            'country' => $country->name,
            'municipality' => $municipality?->name,
            'cap' => $this->cap,
            'street_address' => $this->street_address,
            'house_number' => $this->house_number,
            'locality' => $this->locality,
            'additional_info' => $this->additional_info,
        ], fn($value) => !is_null($value));

        return $returnData;
    }
}
