<?php

namespace Vipertecpro\DataRightsDesk\Events;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Rule 8(2): the person was told her data will be erased unless she comes back. */
class ErasureWarningSent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $subject,
        public CarbonInterface $erasureDueAt,
    ) {}
}
