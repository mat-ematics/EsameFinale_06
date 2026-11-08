<?php

namespace App\Rules;

use App\Helpers\ItalianTaxCodeHelper;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class TaxCodeRule implements DataAwareRule, ValidationRule
{
    protected array $data = [];

    public function __construct(private ?string $iso) {}

    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $code = strtoupper(trim((string) $value));

        // Any other country: free-form tax ID
        if ($this->iso !== 'IT') {
            if (!preg_match('/^[A-Z0-9][A-Z0-9 .\/-]{3,18}[A-Z0-9]$/', $code)) {
                $fail('The :attribute is not valid.');
            }
            return;
        }

        $decoded = ItalianTaxCodeHelper::decode($code);
        if (!$decoded) {
            $fail('The :attribute is not a valid Italian tax code.');
            return;
        }

        foreach (ItalianTaxCodeHelper::mismatches($decoded, $this->data) as $field) {
            $fail("The :attribute does not match the {$field}.");
        }
    }
}