<?php

namespace Vipertecpro\DataRightsDesk\Contracts;

use Illuminate\Database\Eloquent\Model;
use Vipertecpro\DataRightsDesk\Support\ErasureContext;
use Vipertecpro\DataRightsDesk\Support\ErasureOutcome;

/**
 * s.8(7)(a): erases the data one part of the app holds for a purpose when the person withdraws
 * consent for it, such as her newsletter subscription when she withdraws "marketing". Optional:
 * list the class in data-rights.sources. Only data kept for that purpose alone goes; anything
 * another purpose or a law still needs stays, with the reason in the outcome.
 */
interface PurposeEraser
{
    /**
     * The purposes whose withdrawal this source acts on.
     *
     * @return list<string>
     */
    public function purposes(): array;

    public function eraseForPurpose(Model $subject, string $purpose, ErasureContext $context): ErasureOutcome;
}
