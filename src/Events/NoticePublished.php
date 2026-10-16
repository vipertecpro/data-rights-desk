<?php

namespace Vipertecpro\DataRightsDesk\Events;

use Illuminate\Foundation\Events\Dispatchable;

class NoticePublished
{
    use Dispatchable;

    /** @param  list<string>  $locales */
    public function __construct(
        public int $version,
        public array $locales,
    ) {}
}
