<?php

namespace Vipertecpro\DataRightsDesk\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Shared by package records that point at a Data Principal (the "subject").
 */
trait BelongsToSubject
{
    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForSubject(Builder $query, Model $subject): Builder
    {
        return $query
            ->where($this->qualifyColumn('subject_type'), $subject->getMorphClass())
            ->where($this->qualifyColumn('subject_id'), $subject->getKey());
    }

    public function isFor(Model $subject): bool
    {
        return $this->getAttribute('subject_type') === $subject->getMorphClass()
            && (string) $this->getAttribute('subject_id') === (string) $subject->getKey();
    }
}
