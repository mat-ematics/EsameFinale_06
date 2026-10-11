<?php

namespace App\Http\Resources\TvSeries;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TvSeriesCollection extends ResourceCollection
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
                ->map(fn($tvSeries) => (new TvSeriesResource($tvSeries, $this->isAdmin))->toArray($request))
                ->all();
    }
}
