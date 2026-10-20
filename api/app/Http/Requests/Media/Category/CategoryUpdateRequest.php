<?php

namespace App\Http\Requests\Media\Category;

use App\Helpers\AppHelpers;
use App\Models\Media\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CategoryUpdateRequest extends CategoryStoreRequest
{
    protected ?int $categoryId = null;

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
        return AppHelpers::updateRequestRules(parent::rules(), $this->categoryId);
    }
}
