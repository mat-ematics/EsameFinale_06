<?php

namespace App\Http\Requests\Global\Language;

use App\Helpers\AppHelpers;
use App\Models\Global\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LanguageUpdateRequest extends LanguageStoreRequest
{
    protected int $langId = 0;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->langId = is_numeric($this->route('lang')) 
            ? (int) $this->route('lang')
            : optional(Language::where('abbreviation', $this->route('lang'))->first())->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return AppHelpers::updateRequestRules(parent::rules(), $this->langId);
    }
}
