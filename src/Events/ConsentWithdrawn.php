<?php

namespace Vipertecpro\DataRightsDesk\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Vipertecpro\DataRightsDesk\Models\ConsentRecord;
use Vipertecpro\DataRightsDesk\Models\Processor;

/**
 * s.6(6): when consent is withdrawn, the Data Fiduciary and its Data Processors
 * stop processing within a reasonable time. Listen to this event to tell them.
 */
class ConsentWithdrawn
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $subject,
        public string $purpose,
        public ConsentRecord $record,
    ) {}

    /**
     * s.6(6): the processors that got data for this purpose, from your processor register.
     *
     * @return list<array{name: string, kind: string, purposes: list<string>, data_shared: list<string>}>
     */
    public function processors(): array
    {
        return Processor::toTell([$this->purpose]);
    }
}
