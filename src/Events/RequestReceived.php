<?php

namespace Vipertecpro\DataRightsDesk\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Vipertecpro\DataRightsDesk\Models\DataRightsRequest;

class RequestReceived
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public DataRightsRequest $request) {}
}
