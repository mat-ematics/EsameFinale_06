<?php

namespace App\Http\Resources\Episode;

use App\Http\Resources\File\FileCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class EpisodeResource extends JsonResource
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
            $data = Arr::only($data, [
                'name',
                'description',
                'image_path',
                'video_path',
                'season_number',
                'episode_number',
            ]);
        }

        $data['files'] = (new FileCollection($this->files, $this->isAdmin))->toArray();

        return $data;
    }
}
