<?php

namespace Vipertecpro\DataRightsDesk\Exceptions;

class InvalidPurposeDefinition extends DataRightsException
{
    public static function because(string $key, string $reason): self
    {
        return new self("Purpose [{$key}] in config/data-rights.php is not valid: {$reason}");
    }
}
