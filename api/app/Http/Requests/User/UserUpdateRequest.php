<?php

namespace App\Http\Requests\User;

use App\Helpers\AppHelpers;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class UserUpdateRequest extends UserStoreRequest
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
        return AppHelpers::updateRequestRules(Arr::only(parent::rules(), ['username', 'role']));
    }
}
