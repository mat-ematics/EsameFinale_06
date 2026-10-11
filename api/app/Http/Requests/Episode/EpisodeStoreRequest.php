<?php

namespace App\Http\Requests\Episode;

use App\Enums\FileRoleEnum;
use App\Enums\MediaTypeEnum;
use App\Helpers\MediaFileRulesHelper;
use App\Rules\MediaLength;
use App\Rules\UniqueEpisodePerSeason;
use App\Rules\YearRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EpisodeStoreRequest extends FormRequest
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
                'season_number' => 'required|integer|between:1,999',
                'episode_number' => 'required|integer|between:1,99999',
                'description' => 'nullable|string|max:255',
                'length' => ['required', new MediaLength],
                'year' => ['required', new YearRule()],
            ],
            MediaFileRulesHelper::storeRules(MediaTypeEnum::Episode),
        );
    }
}
