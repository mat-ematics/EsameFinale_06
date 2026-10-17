<?php

namespace App\Http\Resources\Global\Language;

use App\Models\Global\Language;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class LanguageCollection extends ResourceCollection
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
            ->map(fn($config) => (new LanguageResource($config, $this->isAdmin))->toArray($request))
            ->all();
    }
}
