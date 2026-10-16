<?php

namespace Vipertecpro\DataRightsDesk\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Fails unless the ticked purposes include the given one, for a form that needs a purpose to go on
 * (for example consent for "account" on a sign-up form). Which purposes your form needs is your decision.
 */
final class IncludesPurpose implements ValidationRule
{
    public function __construct(private readonly string $purpose) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value) || ! in_array($this->purpose, $value, true)) {
            $fail(__('data-rights::public.signup.required', ['purpose' => $this->purpose]));
        }
    }
}
