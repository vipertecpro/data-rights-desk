<?php

namespace Vipertecpro\DataRightsDesk\Verification;

use Illuminate\Database\Eloquent\Model;
use Vipertecpro\DataRightsDesk\Contracts\IdentityVerifier;
use Vipertecpro\DataRightsDesk\Models\DataRightsRequest;

/**
 * Default: a request made while signed in as the person is verified as
 * "signed_in". Anything else (email, phone, a request recorded by staff) needs
 * a person to check and record the method on the dashboard.
 */
class SignedInSubjectVerifier implements IdentityVerifier
{
    public function verify(DataRightsRequest $request, ?Model $signedInUser): ?string
    {
        $subject = $request->subject;

        if ($signedInUser !== null && $subject instanceof Model && $signedInUser->is($subject)) {
            return 'signed_in';
        }

        return null;
    }
}
