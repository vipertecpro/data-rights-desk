<?php

namespace Vipertecpro\DataRightsDesk\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Vipertecpro\DataRightsDesk\Enums\AgeStatus;

/** Staff changed a person's age answer on the dashboard (the person cannot change it herself). */
class AgeAnswerChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $subject,
        public ?AgeStatus $from,
        public AgeStatus $to,
        public ?Model $changedBy,
    ) {}
}
