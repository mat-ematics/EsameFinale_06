<?php

namespace App\Http\Resources\Global;

use App\Http\Resources\Global\ConfigResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ConfigCollection extends ResourceCollection
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
            ->map(fn($config) => (new ConfigResource($config, $this->isAdmin))->toArray($request))
            ->all();
    }
}
