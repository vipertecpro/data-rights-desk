<?php

namespace Vipertecpro\DataRightsDesk\Support;

use Carbon\CarbonInterface;

/**
 * What one Eraser did: how many records it erased and what it kept, with the reason.
 */
final readonly class ErasureOutcome
{
    /**
     * @param  list<array{count: int, reason: string, until: string|null}>  $retained
     */
    private function __construct(
        public int $erasedCount,
        public array $retained,
    ) {}

    public static function erased(int $count): self
    {
        return new self(max(0, $count), []);
    }

    /** Records kept, for example because another law needs them kept (say which in the reason). */
    public static function retained(int $count, string $reason, ?CarbonInterface $until = null): self
    {
        return new self(0, [[
            'count' => max(0, $count),
            'reason' => $reason,
            'until' => $until?->toIso8601String(),
        ]]);
    }

    public static function nothing(): self
    {
        return new self(0, []);
    }

    public function and(ErasureOutcome $other): self
    {
        return new self($this->erasedCount + $other->erasedCount, [...$this->retained, ...$other->retained]);
    }

    public function retainedCount(): int
    {
        return array_sum(array_column($this->retained, 'count'));
    }

    /** @return array{erased: int, retained: list<array{count: int, reason: string, until: string|null}>} */
    public function toArray(): array
    {
        return [
            'erased' => $this->erasedCount,
            'retained' => $this->retained,
        ];
    }
}
