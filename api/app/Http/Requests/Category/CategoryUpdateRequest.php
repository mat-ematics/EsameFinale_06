<?php

namespace App\Http\Requests\Category;

use App\Helpers\AppHelpers;
use App\Models\Media\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CategoryUpdateRequest extends CategoryStoreRequest
{
    protected int $categoryId = 0;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->categoryId = is_numeric($this->route('category')) 
            ? (int) $this->route('category')
            : optional(Category::where('name', $this->route('category'))->first())->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = AppHelpers::updateRequestRules(parent::rules());

        $rules['name'] = [
            'string',
            'max:255',
            Rule::unique('categories', 'name')->ignore($this->categoryId),
        ];

        return $rules;
    }
}
