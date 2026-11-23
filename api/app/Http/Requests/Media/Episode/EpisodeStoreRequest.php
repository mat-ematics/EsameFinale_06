<?php

namespace App\Http\Requests\Media\Episode;


use App\Http\Requests\Media\MediaUploadRules;
use App\Rules\MediaLength;
use Illuminate\Foundation\Http\FormRequest;

class EpisodeStoreRequest extends FormRequest
{
    use MediaUploadRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $creating = $this->isMethod('POST');
        $required = $creating ? 'required' : 'sometimes';

        return [
            'name'           => [$required, 'string', 'max:255'],
            'description'    => ['nullable', 'string', 'max:2000'],
            'season_number'  => [$required, 'integer', 'between:1,100'],
            'episode_number' => [$required, 'integer', 'between:1,9999'],
            'length'         => [$required, new MediaLength()],
            'year'           => [$required, 'integer', 'between:1888,' . $this->maxYear()],

            'cover' => ['sometimes', ...$this->imageRules()],
            'video_upload_id' => [$creating ? 'required' : 'sometimes', 'uuid', $this->uploadRule()],
        ];
    }
}