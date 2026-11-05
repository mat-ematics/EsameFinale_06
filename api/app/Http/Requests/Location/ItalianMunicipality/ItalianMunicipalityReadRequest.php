<?php

namespace App\Http\Requests\Location\ItalianMunicipality;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ItalianMunicipalityReadRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search'      => 'required_without:cap|nullable|string|min:3|max:100',
            'cap'         => 'required_without:search|nullable|digits:5',
            'province_id' => 'nullable|integer',
            'limit'       => 'nullable|integer|min:1|max:25',
        ];
    }
}
