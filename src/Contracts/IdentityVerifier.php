<?php

namespace Vipertecpro\DataRightsDesk\Contracts;

use Illuminate\Database\Eloquent\Model;
use Vipertecpro\DataRightsDesk\Models\DataRightsRequest;

/**
 * Rule 14(1)(b), Rule 14(5): checks that the person asking is the Data Principal.
 */
interface IdentityVerifier
{
    /** The method name when verified, or null when a person must check by hand. */
    public function verify(DataRightsRequest $request, ?Model $signedInUser): ?string;
}
