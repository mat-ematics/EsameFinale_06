<?php

namespace App\Http\Requests\User;

use App\Http\Requests\Auth\RegisterRequest;

class UserStoreRequest extends RegisterRequest
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
        $rules = parent::rules();
        unset($rules['credit']);
        $rules['role'] = 'required|in:admin,user,guest';

        return $rules;
    }
}
