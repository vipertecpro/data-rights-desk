<?php

namespace Vipertecpro\DataRightsDesk\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Vipertecpro\DataRightsDesk\Enums\RequestStatus;
use Vipertecpro\DataRightsDesk\Models\DataRightsRequest;

class RequestStatusChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public DataRightsRequest $request,
        public ?RequestStatus $from,
        public RequestStatus $to,
    ) {}
}
