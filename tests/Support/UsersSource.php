<?php

namespace Vipertecpro\DataRightsDesk\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Vipertecpro\DataRightsDesk\Contracts\Eraser;
use Vipertecpro\DataRightsDesk\Contracts\Exporter;
use Vipertecpro\DataRightsDesk\Support\ErasureContext;
use Vipertecpro\DataRightsDesk\Support\ErasureOutcome;

/** The account row: exported in the summary, deleted on erasure. */
class UsersSource implements Eraser, Exporter
{
    /** @var list<ErasureContext> */
    public static array $contexts = [];

    public function name(): string
    {
        return 'Account';
    }

    public function tables(): array
    {
        return ['users'];
    }

    public function processing(): string
    {
        return 'Your name and email, used to sign you in.';
    }

    public function export(Model $subject): array
    {
        return ['name' => $subject->getAttribute('name'), 'email' => $subject->getAttribute('email')];
    }

    public function erase(Model $subject, ErasureContext $context): ErasureOutcome
    {
        self::$contexts[] = $context;

        if ($context->keepsAccountAccess()) {
            return ErasureOutcome::retained(1, 'Kept so the person can still sign in.');
        }

        $subject->delete();

        return ErasureOutcome::erased(1);
    }
}
