<?php

namespace App\Http\Requests\Film;

use App\Helpers\AppHelpers;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilmUpdateRequest extends FilmStoreRequest
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

        $rules['name'] = [
            'string',
            'max:255',
            Rule::unique('films', 'name')->ignore($this->route('filmId')),
        ];

        return $rules;
    }
}
