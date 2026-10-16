<?php

namespace Vipertecpro\DataRightsDesk\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Vipertecpro\DataRightsDesk\Contracts\PurposeEraser;
use Vipertecpro\DataRightsDesk\Support\ErasureContext;
use Vipertecpro\DataRightsDesk\Support\ErasureOutcome;

/** The newsletter list: kept only for "marketing", so a withdrawal removes the person from it. */
class NewsletterSource implements PurposeEraser
{
    /** @var list<ErasureContext> */
    public static array $contexts = [];

    public function purposes(): array
    {
        return ['marketing'];
    }

    public function eraseForPurpose(Model $subject, string $purpose, ErasureContext $context): ErasureOutcome
    {
        self::$contexts[] = $context;

        return ErasureOutcome::erased(DB::table('drd_subscribers')->where('user_id', $subject->getKey())->delete());
    }
}
