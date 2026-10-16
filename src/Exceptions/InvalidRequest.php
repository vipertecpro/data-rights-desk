<?php

namespace Vipertecpro\DataRightsDesk\Exceptions;

class InvalidRequest extends DataRightsException
{
    public static function notErasure(string $requestId): self
    {
        return new self("Request [{$requestId}] is not an erasure request.");
    }

    public static function withoutSubject(string $requestId): self
    {
        return new self("Request [{$requestId}] is not linked to a person.");
    }
}
