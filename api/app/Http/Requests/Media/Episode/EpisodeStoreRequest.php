<?php

namespace App\Http\Requests\Media\Episode;

use App\Enums\MediaTypeEnum;
use App\Helpers\MediaFileRulesHelper;
use App\Http\Requests\Media\MediaUploadRules;
use App\Rules\MediaLength;
use App\Rules\YearRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EpisodeRequest extends FormRequest
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
            'video' => [$creating ? 'required' : 'sometimes', ...$this->videoRules()],
        ];
    }
}