<?php

namespace App\Http\Requests\Authentication;

use App\Models\Location\Country;
use App\Models\User\Credit;
use App\Models\User\User;
use App\Rules\PostalCodeRule;
use App\Rules\TaxCodeRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as RulesPassword;

class RegisterRequest extends FormRequest
{

    private ?string $countryIso = null;

    private function countryIso(): ?string
    {
        return $this->countryIso ??= Country::whereKey($this->input('country_id'))->value('iso');
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        if ($this->has('cap')) {
            $merge['cap'] = strtoupper(trim((string) $this->input('cap')));
        }
        if ($this->filled('tax_code')) {
            $merge['tax_code'] = strtoupper(trim((string) $this->input('tax_code')));
        }

        $this->merge($merge);
    }

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
            //Informazioni Utente
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:user_profiles,email',
            'birthdate' => 'required|date|before_or_equal:-18 years',
            'gender' => 'required|in:male,female,other',
            'username' => [
                'required', 'string', 'regex:/^[0-9A-Za-z_]{3,32}$/',
                function ($attribute, $value, $fail) {
                    if (User::usernameIsTaken($value)) {
                        $fail('The username has already been taken.');
                    }
                }
            ],
            'password' => ['required', RulesPassword::min((8))->letters()->mixedCase()->numbers()->symbols()],

            //Informazioni Indirizzo
            'country_id' => 'required|integer|exists:countries,id',
            'italian_municipality_id' => [
                Rule::requiredIf(fn () => $this->countryIso() === 'IT'),
                Rule::prohibitedIf(fn () => $this->countryIso() !== 'IT'),
                'nullable', 'integer', 'exists:italian_municipalities,id',
            ],
            'cap' => ['required', 'string', 'max:12', new PostalCodeRule($this->countryIso())],
            'street_address' => 'required|string|max:255',
            'house_number' => 'required|string|max:10',
            'locality' => 'nullable|string|max:255',
            'additional_info' => 'nullable|string|max:255',

            'tax_code' => [
                Rule::requiredIf(fn () => $this->countryIso() === 'IT'),
                'nullable', 'string', 'max:20',
                new TaxCodeRule($this->countryIso()),
                Rule::unique('user_profiles', 'tax_code'),
            ],

            //Informazioni Credito
            'credit' => 'required|numeric|min:0|max:' . Credit::MAX_COUNT,
        ];
    }
}
