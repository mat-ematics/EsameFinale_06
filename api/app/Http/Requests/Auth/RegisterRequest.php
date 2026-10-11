<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as RulesPassword;

class RegisterRequest extends FormRequest
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
            //Informazioni Utente
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:user_profiles,email',
            'birthdate' => 'required|date',
            'gender' => 'required|in:male,female,other',
            'username' => 'required|string|min:3|max:64|unique:users,username',
            'password' => ['required', RulesPassword::min((8))->letters()->mixedCase()->numbers()->symbols()],

            //Informazioni Indirizzo
            'country_id' => 'required|integer|exists:countries,id',
            'italian_municipality_id' => 'required_if:country_id,1|integer|exists:italian_municipalities,id',
            'cap' => 'required|string|size:5',
            'street_address' => 'required|string|max:255',
            'house_number' => 'required|string|max:10',
            'locality' => 'required|string|max:255',
            'additional_info' => 'nullable|string|max:255',

            //Informazioni Credito
            'credit' => 'required|numeric|min:0',
        ];
    }
}
