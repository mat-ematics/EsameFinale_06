<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class YearRule implements ValidationRule
{

    protected int $minYear;
    protected int $maxYear;

    public function __construct(?int $minYear = null, ?int $maxYear = null) {
        $this->minYear = $minYear ?? 1900;
        $this->maxYear = $maxYear;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        //                'year' => 'required|integer|max:3000',

        if (!is_int($value)) {
            $fail("The $attribute must be an integer");
        }

        if ($value < $this->minYear) {
            $fail("The $attribute must be after the year {$this->minYear}");
        }

        if (!is_null($this->maxYear) && $value > $this->maxYear) {
            $fail("The $attribute must be before the year {$this->maxYear}");
        }
    }
}
