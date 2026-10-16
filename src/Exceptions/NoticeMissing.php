<?php

namespace Vipertecpro\DataRightsDesk\Exceptions;

/** s.5(1): the notice comes first. Consent cannot be recorded before a notice is published. */
class NoticeMissing extends DataRightsException
{
    public static function forLocale(string $locale): self
    {
        return new self("No notice is published for locale [{$locale}]. Run php artisan data-rights:notice first.");
    }
}
