<?php

namespace App\Http\Requests\TvSeries;

use App\Enums\FileRoleEnum;
use App\Enums\MediaTypeEnum;
use App\Helpers\MediaFileRulesHelper;
use App\Models\Media\Category;
use App\Rules\YearRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TvSeriesStoreRequest extends FormRequest
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
                'name' => 'required|string|max:255|unique:films,name',
                'description' => 'string|max:255|nullable',
                'categories' => 'required|array|min:1',
                'categories.*' => [
                    'string', 
                    Rule::in(Category::pluck('name')->toArray()),
                ],
                'directors' => 'required|string|max:255',
                'actors' => 'required|string|max:255',
                'start_year' => ['required', new YearRule()],
                'end_year' => ['nullable', new YearRule($this->data()['start_year'] ?? null)],
            ],
            MediaFileRulesHelper::storeRules(MediaTypeEnum::TvSeries),
        );
    }
}
