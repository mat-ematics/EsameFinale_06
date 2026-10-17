<?php

namespace App\Http\Resources\Global\Language;

use App\Models\Global\Language;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class LanguageResource extends JsonResource
{
    protected bool $isAdmin;

    public function __construct(Language $resource, bool $isAdmin = false)
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
            $data = Arr::only($data, ['name', 'abbreviation', 'locale']);
        }

        return $data;
    }
}
