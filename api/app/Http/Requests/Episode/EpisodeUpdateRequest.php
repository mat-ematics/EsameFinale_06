<?php

namespace App\Http\Requests\Episode;

use App\Helpers\AppHelpers;
use App\Rules\UniqueEpisodePerSeason;
use Illuminate\Foundation\Http\FormRequest;

class EpisodeUpdateRequest extends EpisodeStoreRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = AppHelpers::updateRequestRules(parent::rules());

        $rules['tv_series_id'] = [
            'integer',
            'max_digits:20',
            'exists:tv_series,id',
        ];

        return $rules;
    }
}
