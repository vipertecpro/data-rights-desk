<?php

namespace Vipertecpro\DataRightsDesk\Exceptions;

class UnknownPurpose extends DataRightsException
{
    public static function key(string $key): self
    {
        return new self("Unknown data rights purpose [{$key}]. Add it to the purposes in config/data-rights.php.");
    }
}
