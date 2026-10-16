<?php

namespace Vipertecpro\DataRightsDesk\Support;

/**
 * Counts from one run of the retention keeper. Never personal data.
 */
final readonly class RetentionReport
{
    /**
     * @param  array<string, int>  $counts
     * @param  array<string, int>  $models  records erased per HasRetention model class
     */
    public function __construct(
        public bool $pretend,
        public int $keepDays,
        public array $counts,
        public array $models = [],
    ) {}

    public function count(string $key): int
    {
        return $this->counts[$key] ?? 0;
    }

    public function total(): int
    {
        return array_sum($this->counts);
    }

    /** @return array{pretend: bool, keep_days: int, counts: array<string, int>, models: array<string, int>} */
    public function toArray(): array
    {
        return [
            'pretend' => $this->pretend,
            'keep_days' => $this->keepDays,
            'counts' => $this->counts,
            'models' => $this->models,
        ];
    }
}
