<?php

namespace App\Http\Resources\Country;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CountryCollection extends ResourceCollection
{
    protected bool $isAdmin;

    public function __construct($resource, bool $isAdmin = false)
    {
        parent::__construct($resource);
        $this->isAdmin = $isAdmin;
    }
    
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(?Request $request = null): array
    {
        return $this->collection
            ->map(fn($country) => (new CountryResource($country, $this->isAdmin))->toArray($request))
            ->all();
    }
}