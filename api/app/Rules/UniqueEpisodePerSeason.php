<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class UniqueEpisodePerSeason implements ValidationRule
{

    protected $tvSeriesId;
    protected $seasonNumber;
    protected $ignoreId;

    public function __construct($tvSeriesId, $seasonNumber, ?int $ignoreId = null)
    {
        $this->tvSeriesId = $tvSeriesId;
        $this->seasonNumber = $seasonNumber;
        $this->ignoreId = $ignoreId;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = DB::table('episodes')
            ->where('tv_series_id', $this->tvSeriesId)
            ->where('season_number', $this->seasonNumber)
            ->where('episode_number', $value);
        
        if ($this->ignoreId) {
            $query->where('id', '!=', $this->ignoreId);
        }

        if ($query->exists()) {
            $fail('An episode with this number already exists for the given season and series.');
        }
    }

    public function passes($attribute, $value)
    {
        return DB::table('episodes')
            ->where('tv_series_id', $this->tvSeriesId)
            ->where('season_number', $this->seasonNumber)
            ->where('episode_number', $value)
            ->doesntExist();
    }

    public function message()
    {
        return 'An episode with this number already exists for the given season and series.';
    }
}
