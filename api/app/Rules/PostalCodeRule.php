<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PostalCodeRule implements ValidationRule
{
    private const PATTERNS = [
        'IT' => '/^\d{5}$/',
        'FR' => '/^\d{5}$/',
        'DE' => '/^\d{5}$/',
        'ES' => '/^\d{5}$/',
        'CH' => '/^\d{4}$/',
        'AT' => '/^\d{4}$/',
        'PT' => '/^\d{4}-\d{3}$/',
        'NL' => '/^\d{4} ?[A-Z]{2}$/',
        'PL' => '/^\d{2}-\d{3}$/',
        'US' => '/^\d{5}(-\d{4})?$/',
        'CA' => '/^[ABCEGHJ-NPRSTVXY]\d[ABCEGHJ-NPRSTV-Z] ?\d[ABCEGHJ-NPRSTV-Z]\d$/',
        'GB' => '/^[A-Z]{1,2}\d[A-Z\d]? ?\d[A-Z]{2}$/',
    ];

    /** Any other country: 3-11 letters, digits, spaces or hyphens */
    private const GENERIC = '/^[A-Z0-9][A-Z0-9 -]{1,9}[A-Z0-9]$/';

    public function __construct(private ?string $iso) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $pattern = self::PATTERNS[strtoupper((string) $this->iso)] ?? self::GENERIC;

        if (!preg_match($pattern, (string) $value)) {
            $fail('The :attribute is not a valid postal code for the selected country.');
        }
    }
}