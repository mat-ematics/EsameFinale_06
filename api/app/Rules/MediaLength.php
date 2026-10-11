<?php

namespace App\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;

class MediaLength implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $inputTime = Carbon::createFromFormat('H:i:s', $value);
        } catch (\Throwable $th) {
            try {
                $inputTime = Carbon::createFromFormat('H:i', $value)->setSeconds(0);
            } catch (\Throwable $th) {
                $fail('Length must be in the format HH:mm or HH:mm:ss');
                return;
            }
        }

        $maxTime = Carbon::createFromTime(4, 0, 0);

        if ($inputTime->greaterThan($maxTime)) {
            $fail('Media Length must not exceed 4 hours');
        }
    }
}
