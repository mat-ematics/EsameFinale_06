<?php

namespace App\Http\Resources\Location\Country;

use App\Http\Resources\Location\Continent\ContinentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class CountryResource extends JsonResource
{
    protected bool $isAdmin;

    public function __construct($resource, bool $isAdmin = false)
    {
        parent::__construct($resource);
        $this->isAdmin = $isAdmin;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(?Request $request = null): array
    {
        if ($request === null) {
            $request = request();
        }
        
        return $this->getPublicColumns($request);
    }

    /**
     * Return public columns
     */
    protected function getPublicColumns(Request $request) 
    {
        $data = $this->resource->getAttributes();

        
        if (!$this->isAdmin) {
            $data = Arr::only($data, ['name', 'iso', 'iso3', 'phone_prefix']);
        }

        $data['continent'] = new ContinentResource($this->resource->continent, $this->isAdmin);

        return $data;
    }
}
