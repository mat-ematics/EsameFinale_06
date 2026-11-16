<?php
namespace App\Http\Requests\User\UserProfile;

use App\Models\Location\Country;
use App\Models\User\User;
use App\Rules\TaxCodeRule;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as RulesPassword;

class UserProfileUpdateRequest extends FormRequest
{
    private ?string $residenceIso = null;
    private bool $residenceLoaded = false;

    public function authorize(): bool
    {
        return true;
    }

    /** ISO code of the stored residence country: decides whether the tax code is required */
    private function residenceIso(): ?string
    {
        if (!$this->residenceLoaded) {
            $countryId = $this->user()->address?->country_id;
            $this->residenceIso = $countryId ? Country::whereKey($countryId)->value('iso') : null;
            $this->residenceLoaded = true;
        }
        return $this->residenceIso;
    }

    /** The data the tax code must agree with: what is sent, otherwise what is stored */
    private function identity(): array
    {
        $profile = $this->user()->userProfile;

        return [
            'name'      => $this->input('name', $profile?->name),
            'surname'   => $this->input('surname', $profile?->surname),
            'birthdate' => $this->input('birthdate', $profile?->birthdate ? Carbon::parse($profile->birthdate)->toDateString() : null),
            'gender'    => $this->input('gender', $profile?->gender),
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->filled('email')) {
            $merge['email'] = trim((string) $this->input('email'));
        }
        if ($this->filled('tax_code')) {
            $merge['tax_code'] = strtoupper(trim((string) $this->input('tax_code')));
        }

        // Changing who you are re-checks the stored tax code against the new data
        $stored = $this->user()->userProfile?->tax_code;
        if ($this->hasAny(['name', 'surname', 'birthdate', 'gender']) && !$this->has('tax_code') && $stored) {
            $merge['tax_code'] = $stored;
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        $user      = $this->user();
        $profileId = $user->userProfile?->id;

        return [
            // Personal data: each field is optional, but validated when sent
            'name'      => ['sometimes', 'string', 'max:255'],
            'surname'   => ['sometimes', 'string', 'max:255'],
            'email'     => ['sometimes', 'email', 'max:255', Rule::unique('user_profiles', 'email')->ignore($profileId)],
            'birthdate' => ['sometimes', 'date', 'before_or_equal:-18 years'],
            'gender'    => ['sometimes', Rule::in(['male', 'female', 'other'])],

            // 'sometimes': an Italian user without a code can still change other fields.
            // Sending it as null removes it, which is refused for residents of Italy.
            'tax_code' => [
                'sometimes',
                Rule::requiredIf(fn () => $this->residenceIso() === 'IT'),
                'nullable', 'string', 'max:20',
                new TaxCodeRule($this->residenceIso(), $this->identity()),
                Rule::unique('user_profiles', 'tax_code')->ignore($profileId),
            ],

            // Credentials
            'username' => [
                'sometimes', 'string', 'regex:/^[0-9A-Za-z_]{3,32}$/',
                function ($attribute, $value, $fail) use ($user) {
                    if (User::usernameIsTaken($value, $user->id)) {
                        $fail('The username has already been taken.');
                    }
                },
            ],
            'password' => [
                'sometimes',
                RulesPassword::min(8)->letters()->mixedCase()->numbers()->symbols(),
                'different:current_password',
            ],
            // A wrong value must be a 422, NOT a 401: the Angular interceptor would sign the user out
            'current_password' => [
                'required_with:username,password', 'string',
                function ($attribute, $value, $fail) use ($user) {
                    $current = $user->currentPassword;

                    if (!$current || !$current->checkPassword($value)) {
                        $fail('The current password is incorrect.');
                    }
                },
            ],
        ];
    }
}