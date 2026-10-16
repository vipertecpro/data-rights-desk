<?php

namespace Vipertecpro\DataRightsDesk\Contracts;

use Illuminate\Database\Eloquent\Model;
use Vipertecpro\DataRightsDesk\Support\ErasureContext;
use Vipertecpro\DataRightsDesk\Support\ErasureOutcome;

/**
 * s.8(7), s.12(3), Rule 8: erases the personal data one part of the app holds
 * about a person, keeping what another law needs (say why in the outcome).
 */
interface Eraser
{
    public function name(): string;

    /**
     * Tables this source covers, for the checker.
     *
     * @return list<string>
     */
    public function tables(): array;

    public function erase(Model $subject, ErasureContext $context): ErasureOutcome;
}
