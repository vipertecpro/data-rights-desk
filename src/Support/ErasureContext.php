<?php

namespace Vipertecpro\DataRightsDesk\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Vipertecpro\DataRightsDesk\Enums\ErasureReason;
use Vipertecpro\DataRightsDesk\Models\LegalHold;

/**
 * Passed to every Eraser: why the data is being erased, which records are on
 * legal hold, and how long processing logs must still be kept (Rule 8(3)).
 */
final readonly class ErasureContext
{
    public function __construct(
        public ErasureReason $reason,
        private Model $subject,
        private int $keepDays,
        /** The withdrawn purpose, for a PurposeEraser; null for a whole-person erasure. */
        public ?string $purpose = null,
    ) {}

    public function subject(): Model
    {
        return $this->subject;
    }

    /** True when a legal hold covers this record: keep it. */
    public function isHeld(Model $record): bool
    {
        return LegalHold::covers($record);
    }

    public function subjectIsHeld(): bool
    {
        return LegalHold::covers($this->subject);
    }

    /** Rule 8(3): personal data and logs of processing are kept at least one year from the processing. */
    public function keepUntil(CarbonInterface $processedAt): CarbonInterface
    {
        return CarbonImmutable::instance($processedAt)->addDays($this->keepDays);
    }

    /**
     * Third Schedule exceptions (a) and (b): on inactivity erasure keep what the
     * person needs to access her account and any stored-value virtual token.
     */
    public function keepsAccountAccess(): bool
    {
        return $this->reason === ErasureReason::Inactivity;
    }
}
