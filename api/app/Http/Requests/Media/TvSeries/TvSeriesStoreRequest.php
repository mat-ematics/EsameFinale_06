<?php

namespace App\Http\Requests\Media\TvSeries;

use App\Enums\MediaTypeEnum;
use App\Helpers\MediaFileRulesHelper;
use App\Models\Media\Category;
use App\Rules\YearRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

namespace App\Http\Requests\Media;

use App\Models\Media\TvSeries;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TvSeriesStoreRequest extends FormRequest
{
    use MediaUploadRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $creating = $this->isMethod('POST');
        $required = $creating ? 'required' : 'sometimes';
        $series   = $this->route('tvSeriesId') ? TvSeries::withTrashed()->find($this->route('tvSeriesId')) : null;
        $max      = $this->maxYear();

        return [
            'name'         => [$required, 'string', 'max:255', Rule::unique('tv_series', 'name')->ignore($series?->id)],
            'description'  => ['nullable', 'string', 'max:2000'],
            'directors'    => [$required, 'string', 'max:255'],
            'actors'       => [$required, 'string', 'max:255'],
            'start_year'   => [$required, 'integer', "between:1888,$max"],
            'end_year'     => [
                'nullable', 'integer', "between:1888,$max",
                function ($attribute, $value, $fail) use ($series) {
                    $start = (int) $this->input('start_year', $series?->start_year);
                    if ($start && (int) $value < $start) $fail('The end year cannot be before the start year.');
                },
            ],
            'categories'   => [$required, 'array', 'min:1'],
            'categories.*' => ['string', Rule::exists('categories', 'name')],

            'cover'         => [$creating ? 'required' : 'sometimes', ...$this->imageRules()],
            'banner'        => ['sometimes', ...$this->imageRules()],
            'remove_banner' => ['sometimes', 'boolean'],
        ];
    }
}