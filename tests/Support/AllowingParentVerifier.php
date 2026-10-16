<?php

namespace Vipertecpro\DataRightsDesk\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Vipertecpro\DataRightsDesk\Contracts\ParentalConsentVerifier;
use Vipertecpro\DataRightsDesk\Enums\GuardianKind;
use Vipertecpro\DataRightsDesk\Enums\GuardianMethod;
use Vipertecpro\DataRightsDesk\Support\GuardianVerification;

/** A stand-in for an app's real Rule 10 check. */
class AllowingParentVerifier implements ParentalConsentVerifier
{
    public function verify(Model $subject, Request $request): GuardianVerification
    {
        return new GuardianVerification(
            GuardianKind::Parent,
            GuardianMethod::DetailsProvided,
            null,
            'Parent Name',
            'token-123',
        );
    }
}
