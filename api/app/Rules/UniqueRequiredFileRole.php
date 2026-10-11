<?php

namespace App\Rules;

use App\Helpers\AppHelpers;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueRequiredFileRole implements ValidationRule
{

    protected string $requiredRole;

    public function __construct(string $requiredRole)
    {
        $this->requiredRole = $requiredRole;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail("The $attribute must be an array");
        }

        $roles = AppHelpers::extractColumnFromArray($value, 'role');

        $count = AppHelpers::countValuesInArray($roles)[$this->requiredRole] ?? 0;

        if ($count > 1) {
            $fail("Only one file can have the role '{$this->requiredRole}'");
        }
    }
}
