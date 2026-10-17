<?php

namespace Vipertecpro\DataRightsDesk\Support;

use Vipertecpro\DataRightsDesk\Enums\ErasureReason;

/**
 * The outcome of erasing a person's data: one outcome per Eraser, or "held"
 * when a legal hold on the person stopped the erasure. Counts and reasons only.
 */
final readonly class ErasureReport
{
    /**
     * @param  array<string, ErasureOutcome>  $sources
     * @param  list<array{name: string, kind: string, purposes: list<string>, data_shared: list<string>}>  $processors  s.8(7)(b): the processors that got data for these purposes, to be told to erase it
     */
    public function __construct(
        public ErasureReason $reason,
        public array $sources = [],
        public bool $held = false,
        public int $nominationsDeleted = 0,
        public array $processors = [],
    ) {}

    public static function blockedByHold(ErasureReason $reason): self
    {
        return new self($reason, [], true);
    }

    public function erasedCount(): int
    {
        return array_sum(array_map(fn (ErasureOutcome $o): int => $o->erasedCount, $this->sources));
    }

    public function retainedCount(): int
    {
        return array_sum(array_map(fn (ErasureOutcome $o): int => $o->retainedCount(), $this->sources));
    }

    /**
     * Reasons for kept records, one line per reason.
     *
     * @return list<string>
     */
    public function retainedReasons(): array
    {
        $reasons = [];

        foreach ($this->sources as $outcome) {
            foreach ($outcome->retained as $item) {
                $reasons[] = $item['reason'];
            }
        }

        return array_values(array_unique($reasons));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'reason' => $this->reason->value,
            'held' => $this->held,
            'erased' => $this->erasedCount(),
            'retained' => $this->retainedCount(),
            'nominations_deleted' => $this->nominationsDeleted,
            'sources' => array_map(fn (ErasureOutcome $o): array => $o->toArray(), $this->sources),
            'processors_to_tell' => $this->processors,
        ];
    }
}
