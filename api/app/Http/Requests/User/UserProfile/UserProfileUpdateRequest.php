<?php

namespace App\Http\Requests\User\UserProfile;

use App\Helpers\AppHelpers;
use App\Http\Requests\Authentication\RegisterRequest;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends RegisterRequest
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
        return AppHelpers::updateRequestRules(parent::rules());
    }
}
