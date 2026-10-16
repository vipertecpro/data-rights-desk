<?php

namespace Vipertecpro\DataRightsDesk\Http\Controllers\Desk;

use Illuminate\Contracts\View\View;
use Vipertecpro\DataRightsDesk\Models\GuardianConsent;
use Vipertecpro\DataRightsDesk\Models\SubjectProfile;
use Vipertecpro\DataRightsDesk\Verification\NoParentalConsentVerifier;

/** s.9, Rules 10-12: counts and guardian consents. */
class ChildrenController
{
    public function __invoke(): View
    {
        return view('data-rights::desk.children', [
            'enabled' => (bool) config('data-rights.children.enabled', true),
            'unknownIsChild' => (bool) config('data-rights.children.unknown_age_is_child', false),
            'adults' => SubjectProfile::query()->where('age_status', 'adult')->count(),
            'children' => SubjectProfile::query()->where('age_status', 'child')->count(),
            'withGuardian' => SubjectProfile::query()->where('has_lawful_guardian', true)->count(),
            'consents' => GuardianConsent::query()->with('subject')->orderByDesc('verified_at')->limit(100)->get(),
            'verifierBound' => ! NoParentalConsentVerifier::inUse(),
        ]);
    }
}
