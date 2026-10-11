<?php

namespace App\Http\Requests\Film;

use App\Enums\MediaTypeEnum;
use App\Helpers\MediaFileRulesHelper;
use App\Models\Media\Category;
use App\Rules\MediaLength;
use App\Rules\YearRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilmStoreRequest extends FormRequest
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
        return array_merge(
            [
                /* Validazione Film */
                'name' => 'required|string|max:255|unique:films,name',
                'description' => 'string|max:255|nullable',
                'categories' => 'required|array|min:1',
                'categories.*' => [
                    'string', 
                    Rule::in(Category::pluck('name')->toArray()),
                ],
                'length' => ['required', new MediaLength],
                'directors' => 'required|string|max:255',
                'actors' => 'required|string|max:255',
                'year' => ['required', new YearRule()],
            ],
            MediaFileRulesHelper::storeRules(MediaTypeEnum::Film),
        );
    }
}
