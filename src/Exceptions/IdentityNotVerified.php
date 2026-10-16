<?php

namespace Vipertecpro\DataRightsDesk\Exceptions;

/** Rule 14(1)(b): check who is asking before acting on a request. */
class IdentityNotVerified extends DataRightsException
{
    public static function forRequest(string $requestId): self
    {
        return new self("Check the identity of the person behind request [{$requestId}] first.");
    }
}
