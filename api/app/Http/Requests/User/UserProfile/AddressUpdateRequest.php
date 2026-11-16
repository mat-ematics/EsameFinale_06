<?php

namespace App\Http\Requests\User\UserProfile;

use App\Models\Location\Country;
use App\Rules\PostalCodeRule;
use App\Rules\TaxCodeRule;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class AddressUpdateRequest extends FormRequest
{
    private ?string $countryIso = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    private function countryIso(): ?string
    {
        return $this->countryIso ??= Country::whereKey($this->input('country_id'))->value('iso');
    }

    #[Override]
    protected function prepareForValidation()
    {
        if ($this->has('cap')) {
            $this->merge(['cap' => strtoupper(trim((string) $this->input('cap')))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'country_id' => [
                'required', 'integer', 'exists:countries,id',
                function ($attribute, $value, $fail) {
                    if ($this->countryIso() === 'IT' && empty($this->user()->userProfile?->tax_code)) {
                        $fail('Add your tax code in your personal data first: it is required for Italian addresses.');
                    }
                },
            ],
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
                Rule::unique('user_profiles', 'tax_code')->ignore($this->user()->userProfile?->id),
            ],
        ];
    }
}
