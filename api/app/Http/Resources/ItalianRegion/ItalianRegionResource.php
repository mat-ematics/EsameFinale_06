<?php

namespace App\Http\Resources\ItalianRegion;

use App\Http\Resources\Continent\ContinentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class ItalianRegionResource extends JsonResource
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
            $data = Arr::only($data, ['name', 'code']);
        }

        return $data;
    }
}
