<?php

namespace Vipertecpro\DataRightsDesk\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Vipertecpro\DataRightsDesk\Support\ErasureReport;

/**
 * Carries the subject's type and key only: the subject row may already be gone.
 */
class PersonalDataErased
{
    use Dispatchable;

    public function __construct(
        public string $subjectType,
        public int|string $subjectId,
        public ErasureReport $report,
    ) {}
}
