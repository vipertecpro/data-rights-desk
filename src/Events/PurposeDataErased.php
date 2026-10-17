<?php

namespace Vipertecpro\DataRightsDesk\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Vipertecpro\DataRightsDesk\Support\ErasureReport;

/**
 * s.8(7)(a): the PurposeEraser sources ran after the person withdrew consent for one purpose.
 * The person still exists; only the data for that purpose went. The report lists the
 * processors that got data for the purpose, to be told to erase it too (s.8(7)(b)).
 */
class PurposeDataErased
{
    use Dispatchable;

    public function __construct(
        public string $subjectType,
        public int|string $subjectId,
        public string $purpose,
        public ErasureReport $report,
    ) {}
}
