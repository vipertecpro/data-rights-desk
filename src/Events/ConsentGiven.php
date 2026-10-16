<?php

namespace Vipertecpro\DataRightsDesk\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Vipertecpro\DataRightsDesk\Models\ConsentRecord;

class ConsentGiven
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $subject,
        public string $purpose,
        public ConsentRecord $record,
    ) {}
}
