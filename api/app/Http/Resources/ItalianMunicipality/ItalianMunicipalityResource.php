<?php

namespace App\Http\Resources\ItalianMunicipality;

use App\Http\Resources\ItalianProvince\ItalianProvinceResource;
use App\Http\Resources\ItalianRegion\ItalianRegionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class ItalianMunicipalityResource extends JsonResource
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
            $data = Arr::only($data, ['name', 'code', 'catastal_code', 'cap']);
        }

        $data['province'] = new ItalianProvinceResource($this->resource->province, $this->isAdmin);

        return $data;
    }
}
