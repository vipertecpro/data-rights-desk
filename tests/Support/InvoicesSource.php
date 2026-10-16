<?php

namespace Vipertecpro\DataRightsDesk\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Vipertecpro\DataRightsDesk\Contracts\Eraser;
use Vipertecpro\DataRightsDesk\Support\ErasureContext;
use Vipertecpro\DataRightsDesk\Support\ErasureOutcome;

/** An eraser that keeps records another law needs. */
class InvoicesSource implements Eraser
{
    public function name(): string
    {
        return 'Invoices';
    }

    public function tables(): array
    {
        return ['invoices'];
    }

    public function erase(Model $subject, ErasureContext $context): ErasureOutcome
    {
        return ErasureOutcome::erased(1)->and(ErasureOutcome::retained(2, 'Tax invoices are kept for 8 years under the GST Act.', now()->addYears(8)));
    }
}
