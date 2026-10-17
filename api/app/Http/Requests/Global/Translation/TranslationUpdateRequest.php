<?php

namespace App\Http\Requests\Global\Translation;

use App\Helpers\AppHelpers;
use App\Models\Global\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class TranslationUpdateRequest extends TranslationStoreRequest
{
    protected int $langId = 0;

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
        return AppHelpers::updateRequestRules(parent::rules(), $this->langId);
    }
}
