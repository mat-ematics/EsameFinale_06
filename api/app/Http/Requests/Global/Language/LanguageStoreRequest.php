<?php

namespace App\Http\Requests\Global\Language;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LanguageStoreRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('languages', 'name')],
            'abbreviation' => ['required', 'string', 'max:6', Rule::unique('languages', 'abbreviation')],
            'locale' => ['sometimes', 'string', 'max:10', Rule::unique('languages', 'locale')],
        ];
    }
}
