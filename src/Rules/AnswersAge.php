<?php

namespace Vipertecpro\DataRightsDesk\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * s.9(1): the age question of <x-data-rights::consent-fields ask-age> must be answered, with
 * "adult" or "child". It runs even when nothing is chosen, so `required` is not needed.
 */
final class AnswersAge implements ValidationRule
{
    /** Run when the field is missing too: with no answer chosen, the browser sends nothing. */
    public bool $implicit = true;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! in_array($value, ['adult', 'child'], true)) {
            $fail(__('data-rights::public.signup.age_required'));
        }
    }
}
