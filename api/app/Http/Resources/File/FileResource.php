<?php

namespace App\Http\Resources\File;

use App\Enums\FileVisibilityEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class FileResource extends JsonResource
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

    private function isPublicFile(): bool
    {
        return FileVisibilityEnum::tryFrom($this->visibility) === FileVisibilityEnum::Public;
    }

    /**
     * Return public columns
     */
    protected function getPublicColumns(Request $request) 
    {
        $data = $this->resource->getAttributes();

        if (!$this->isAdmin) {
            $data = Arr::only($data, [ 
                'label',
                'role',
                'size',
                'mime_type',
                'extension',
            ]);
        }

        $data['url'] = $this->when($this->isPublicFile(), $this->getUrl());

        return !$this->isPublicFile() && !$this->isAdmin ? null : $data;
    }
}
