<?php

namespace Vipertecpro\DataRightsDesk\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Vipertecpro\DataRightsDesk\Models\Breach;

class BreachRecorded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Breach $breach) {}
}
