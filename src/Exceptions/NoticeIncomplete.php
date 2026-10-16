<?php

namespace Vipertecpro\DataRightsDesk\Exceptions;

class NoticeIncomplete extends DataRightsException
{
    /** @param  list<string>  $problems */
    public function __construct(public readonly array $problems)
    {
        parent::__construct('The notice cannot be published: '.implode('; ', $problems));
    }
}
