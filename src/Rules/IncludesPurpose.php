<?php

namespace Vipertecpro\DataRightsDesk\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;
use Vipertecpro\DataRightsDesk\Exceptions\UnknownPurpose;
use Vipertecpro\DataRightsDesk\Notices\NoticeRepository;
use Vipertecpro\DataRightsDesk\Purposes\PurposeRegistry;

/**
 * Fails unless the ticked purposes include the given one, for a form that needs a purpose to go on
 * (for example consent for "account" on a sign-up form). Which purposes your form needs is your decision.
 * It runs even when nothing is ticked (the field is then missing), so `required` is not needed.
 */
final class IncludesPurpose implements ValidationRule
{
    /** Run when the field is missing too: with no box ticked, the browser sends nothing. */
    public bool $implicit = true;

    /**
     * @throws UnknownPurpose when the purpose is not in config/data-rights.php
     * @throws InvalidArgumentException when the purpose does not need consent
     */
    public function __construct(private readonly string $purpose)
    {
        if (! app(PurposeRegistry::class)->get($purpose)->needsConsent()) {
            throw new InvalidArgumentException("Purpose [{$purpose}] does not need consent, so a form cannot require consent for it.");
        }
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // s.5(1): without a published notice, consent cannot be recorded, so stop the form here
        // rather than create an account that has no consent behind it.
        if (app(NoticeRepository::class)->current() === null) {
            $fail(__('data-rights::public.signup.no_notice'));

            return;
        }

        if (! is_array($value) || ! in_array($this->purpose, $value, true)) {
            $fail(__('data-rights::public.signup.required', ['purpose' => $this->purpose]));
        }
    }
}
