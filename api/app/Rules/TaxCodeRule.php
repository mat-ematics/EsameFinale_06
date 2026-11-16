<?php

namespace App\Rules;

use App\Helpers\ItalianTaxCodeHelper;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class TaxCodeRule implements DataAwareRule, ValidationRule
{
    /** Same shape as the structure check in ItalianTaxCode (omocodia included) */
    private const CF_SHAPE = 
        '/^[A-Z]{6}[0-9LMNPQRSTUV]{2}[ABCDEHLMPRST][0-9LMNPQRSTUV]{2}[A-Z][0-9LMNPQRSTUV]{3}[A-Z]$/';

    protected array $data = [];

    public function __construct(private ?string $iso, private ?array $identity = null ) {}

    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $code = strtoupper(trim((string) $value));

        // Italian rules apply in Italy and to anything shaped like a codice fiscale
        $italian = $this->iso === 'IT' || preg_match(self::CF_SHAPE, $code) === 1;

        // Any other country: free-form tax ID
        if (!$italian) {
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

        foreach (ItalianTaxCodeHelper::mismatches($decoded, $this->identity ?? $this->data) as $field) {
            $fail("The :attribute does not match the {$field}.");
        }
    }
}