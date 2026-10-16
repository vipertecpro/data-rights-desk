<?php

namespace Vipertecpro\DataRightsDesk\Exceptions;

use Vipertecpro\DataRightsDesk\Enums\RequestStatus;

class InvalidTransition extends DataRightsException
{
    public static function between(string $requestId, RequestStatus $from, RequestStatus $to): self
    {
        return new self("Request [{$requestId}] cannot move from [{$from->value}] to [{$to->value}].");
    }
}
