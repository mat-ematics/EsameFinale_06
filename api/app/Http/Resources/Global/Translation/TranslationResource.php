<?php

namespace App\Http\Resources\Global\Translation;

use App\Http\Resources\Global\Language\LanguageResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class TranslationResource extends JsonResource
{
    protected bool $isAdmin;

    public function __construct(Model $resource, bool $isAdmin = false)
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
            $data = Arr::only($data, ['key', 'value']);
        }

        $data['language'] = new LanguageResource($this->resource->language, $this->isAdmin);

        return $data;
    }
}
