<?php

namespace Vipertecpro\DataRightsDesk\View;

use Illuminate\Database\Eloquent\Model;
use Vipertecpro\DataRightsDesk\Facades\DataRights;

/**
 * Backs @dataRightsAllows and @dataRightsTracking.
 */
final class Directives
{
    public static function allows(string $purpose): bool
    {
        $user = auth()->user();

        return $user instanceof Model && DataRights::allows($user, $purpose);
    }

    /** s.9(3): renders only when the signed-in person may be tracked. */
    public static function tracking(): bool
    {
        $user = auth()->user();

        return DataRights::children()->mayTrack($user instanceof Model ? $user : null);
    }
}
