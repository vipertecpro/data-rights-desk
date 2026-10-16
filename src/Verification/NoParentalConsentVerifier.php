<?php

namespace Vipertecpro\DataRightsDesk\Verification;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Vipertecpro\DataRightsDesk\Contracts\ParentalConsentVerifier;
use Vipertecpro\DataRightsDesk\Support\GuardianVerification;

/**
 * Default: always refuses. Rule 10 asks for due diligence that the parent is
 * an identifiable adult; only the app can do that, so it must bind its own
 * Contracts\ParentalConsentVerifier.
 */
class NoParentalConsentVerifier implements ParentalConsentVerifier
{
    /** True while the app has not bound its own verifier. */
    public static function inUse(): bool
    {
        return self::isDefault(app(ParentalConsentVerifier::class));
    }

    private static function isDefault(ParentalConsentVerifier $verifier): bool
    {
        return $verifier instanceof self;
    }

    public function verify(Model $subject, Request $request): GuardianVerification
    {
        throw ValidationException::withMessages([
            'guardian' => (string) __('data-rights::public.guardian_verifier_missing'),
        ]);
    }
}
