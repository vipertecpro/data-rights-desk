<?php

namespace Vipertecpro\DataRightsDesk\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Vipertecpro\DataRightsDesk\Models\SubjectProfile;

/**
 * data-rights.activity: Rule 8(1)-(2). Notes when the person was last seen
 * (at most once a day), clears a pending erasure warning, and clears a past
 * erasure when an erased person whose account was kept comes back.
 */
class RecordActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof Model) {
            $profile = SubjectProfile::for($user);

            if ($profile->last_seen_at === null
                || ! $profile->last_seen_at->isToday()
                || $profile->erasure_warned_at !== null
                || $profile->erased_at !== null) {
                SubjectProfile::markActive($user);
            }
        }

        return $next($request);
    }
}
