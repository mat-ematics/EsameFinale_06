<?php

namespace App\Http\Requests\User\UserProfile;

use App\Helpers\AppHelpers;
use App\Models\User\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRules;

class UserProfileUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('email')) {
            $this->merge(['email' => trim((string) $this->input('email'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        $profileId = $user->userProfile?->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'surname' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'max:255', Rule::unique('user_profile', 'email')->ignore($profileId)],
            'birthdate' => ['sometimes', 'date', 'before_or_equal:-18 years'],
            'gender' => ['sometimes', Rule::in(['male', 'female', 'other'])],

            'username' => [
                'sometimes', 'string', 'regex:/^[0-9A-Za-z_]{3,32}$/',
                function ($attribute, $value, $fail) use ($user) {
                    if (User::usernameIsTaken($value, $user->id)) {
                        $fail('The username is already taken.');
                    }
                }
            ],

            'password' => [
                'sometimes',
                PasswordRules::min(8)->letters()->mixedCase()->numbers()->symbols(),
                'different:current_password',
            ],

            'current_password' => [
                'required_with:username,password', 'string',
                function ($attribute, $value, $fail) use ($user) {
                    $current = $user->currentPassword;

                    if (!$current || !$current->checkPassword($value)) {
                        $fail('The current password is incorrect.');
                    }
                }
            ]
        ];
    }
}
