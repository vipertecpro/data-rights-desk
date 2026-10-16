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

    public static function erasureNeedsErase(string $requestId): self
    {
        return new self("Request [{$requestId}] is an erasure request. Use Erase, which runs your Erasers and then replies to the person.");
    }

    public static function noErasers(): self
    {
        return new self('No Eraser is listed in data-rights.sources, so nothing can be erased and the person has not been told anything. Add one (php artisan data-rights:make-source) and try again.');
    }
}
