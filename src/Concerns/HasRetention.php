<?php

namespace Vipertecpro\DataRightsDesk\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Vipertecpro\DataRightsDesk\Support\Limits;

/**
 * For app models the retention keeper erases once their purpose is served
 * (s.8(7)). List the model in config('data-rights.retention.models').
 *
 * Records older than max(retentionDays(), keep_days) are erased, so nothing
 * goes before the one-year minimum of Rule 8(3). Records on legal hold stay.
 */
trait HasRetention
{
    /** The purpose key these records serve. */
    abstract public function retentionPurpose(): string;

    public function retentionDays(): int
    {
        return Limits::keepDays();
    }

    public function retentionDateColumn(): string
    {
        return $this->getUpdatedAtColumn() ?? 'updated_at';
    }

    /**
     * Narrow the records that may be erased (for example: only closed orders).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function retentionQuery(Builder $query): Builder
    {
        return $query;
    }

    public function eraseForRetention(): void
    {
        $this->delete();
    }
}
