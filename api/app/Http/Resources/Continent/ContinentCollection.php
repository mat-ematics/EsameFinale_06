<?php

namespace App\Http\Resources\Continent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ContinentCollection extends ResourceCollection
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
            ->map(fn($film) => (new ContinentResource($film, $this->isAdmin))->toArray($request))
            ->all();
    }
}
