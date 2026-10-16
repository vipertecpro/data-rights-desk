<?php

namespace Vipertecpro\DataRightsDesk\Http\Controllers\Desk;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Vipertecpro\DataRightsDesk\DataRightsManager;
use Vipertecpro\DataRightsDesk\Models\GuardianConsent;
use Vipertecpro\DataRightsDesk\Models\SubjectProfile;
use Vipertecpro\DataRightsDesk\Support\Subjects;
use Vipertecpro\DataRightsDesk\Verification\NoParentalConsentVerifier;

/** s.9, Rules 10-12: counts, guardian consents, and correcting an age answer. */
class ChildrenController
{
    public function __construct(private DataRightsManager $dataRights) {}

    public function __invoke(Request $http): View
    {
        // Looked up by POST so the email never lands in a URL, the access log or the browser history.
        $email = $http->isMethod('post') ? $http->input('email') : null;
        $subject = is_string($email) && $email !== '' ? Subjects::findByEmail($email) : null;

        return view('data-rights::desk.children', [
            'enabled' => (bool) config('data-rights.children.enabled', true),
            'unknownIsChild' => (bool) config('data-rights.children.unknown_age_is_child', false),
            'adults' => SubjectProfile::query()->where('age_status', 'adult')->count(),
            'children' => SubjectProfile::query()->where('age_status', 'child')->count(),
            'withGuardian' => SubjectProfile::query()->where('has_lawful_guardian', true)->count(),
            'consents' => GuardianConsent::query()->with('subject')->orderByDesc('verified_at')->limit(100)->get(),
            'verifierBound' => ! NoParentalConsentVerifier::inUse(),
            'email' => is_string($email) ? $email : '',
            'subject' => $subject,
            'profile' => $subject !== null ? SubjectProfile::lookup($subject) : null,
        ]);
    }

    /** Staff change a person's age answer, with a note. */
    public function changeAge(Request $http): RedirectResponse
    {
        $data = $http->validate([
            'email' => ['required', 'string', 'max:255'],
            'is_adult' => ['required', 'in:0,1'],
            'note' => ['required', 'string', 'max:2000'],
        ]);

        $subject = Subjects::findByEmail((string) $data['email']);

        if ($subject === null) {
            return redirect()->route('data-rights-desk.children')->withErrors(['email' => 'No person with that email.']);
        }

        $user = $http->user();

        $this->dataRights->children()->changeAge($subject, $data['is_adult'] === '1', (string) $data['note'], $user instanceof Model ? $user : null);

        return redirect()->route('data-rights-desk.children')->with('data-rights.status', 'Age answer changed. The note is stored encrypted.');
    }
}
