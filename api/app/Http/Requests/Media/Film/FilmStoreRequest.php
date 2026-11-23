<?php

namespace App\Http\Requests\Media\Film;

use App\Http\Requests\Media\MediaUploadRules;
use App\Rules\MediaLength;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilmStoreRequest extends FormRequest
{
    use MediaUploadRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $creating = $this->isMethod('POST');           // an update is a POST with _method=PUT, which reports as PUT
        $required = $creating ? 'required' : 'sometimes';

        return [
            'name'          => [$required, 'string', 'max:255', Rule::unique('films', 'name')->ignore($this->route('filmId'))],
            'description'   => ['nullable', 'string', 'max:2000'],
            'length'        => [$required, new MediaLength()],
            'directors'     => [$required, 'string', 'max:255'],
            'actors'        => [$required, 'string', 'max:255'],
            'year'          => [$required, 'integer', 'between:1888,' . $this->maxYear()],
            'categories'    => [$required, 'array', 'min:1'],
            'categories.*'  => ['string', Rule::exists('categories', 'name')],

            'cover'         => [$creating ? 'required' : 'sometimes', ...$this->imageRules()],
            'banner'        => ['sometimes', ...$this->imageRules()],
            'video_upload_id' => [$creating ? 'required' : 'sometimes', 'uuid', $this->uploadRule()],
            'remove_banner' => ['sometimes', 'boolean'],
        ];
    }
}