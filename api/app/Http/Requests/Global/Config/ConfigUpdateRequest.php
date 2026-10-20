<?php

namespace App\Http\Requests\Global\Config;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfigUpdateRequest extends FormRequest
{
    protected ?int $configId = null;

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
        $rules = [
            'value' => 'sometimes|required|integer|min:0|max_digits:10',
        ];

        return $rules;
    }
}
