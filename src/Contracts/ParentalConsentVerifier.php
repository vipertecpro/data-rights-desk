<?php

namespace Vipertecpro\DataRightsDesk\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Vipertecpro\DataRightsDesk\Support\GuardianVerification;

/**
 * s.9(1), Rules 10 and 11: checks that the person giving consent for a child
 * (or for a person with disability) is an identifiable adult parent or a
 * lawfully appointed guardian. The app provides this; the package cannot.
 */
interface ParentalConsentVerifier
{
    /** @throws ValidationException when the check fails */
    public function verify(Model $subject, Request $request): GuardianVerification;
}
