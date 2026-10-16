<?php

namespace Vipertecpro\DataRightsDesk\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Vipertecpro\DataRightsDesk\Models\SubjectProfile;

/**
 * data-rights.activity: Rule 8(1)-(2). Notes when the person was last seen
 * (at most once a day) and clears a pending erasure warning.
 */
class RecordActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof Model) {
            $profile = SubjectProfile::for($user);

            if ($profile->last_seen_at === null || ! $profile->last_seen_at->isToday() || $profile->erasure_warned_at !== null) {
                $profile->forceFill([
                    'last_seen_at' => now(),
                    'erasure_warned_at' => null,
                    'erasure_due_at' => null,
                ])->save();
            }
        }

        return $next($request);
    }
}
