<?php

namespace Vipertecpro\DataRightsDesk\Http\Controllers\Desk;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Vipertecpro\DataRightsDesk\DataRightsManager;
use Vipertecpro\DataRightsDesk\Support\Subjects;

class ConsentController
{
    public function __construct(private DataRightsManager $dataRights, private Summary $summary) {}

    public function index(Request $http): View
    {
        $email = $http->query('email');
        $subject = is_string($email) && $email !== '' ? Subjects::findByEmail($email) : null;

        return view('data-rights::desk.consents', [
            'purposes' => $this->summary->overview($this->dataRights)['purposes'],
            'email' => is_string($email) ? $email : '',
            'subject' => $subject,
            'current' => $subject !== null ? $this->dataRights->consents()->current($subject) : [],
            'history' => $subject !== null ? $this->dataRights->consents()->history($subject) : collect(),
            'allPurposes' => $this->dataRights->purposes()->all(),
        ]);
    }

    /** s.6(10): download the proof JSON for one person. */
    public function proof(Request $http): JsonResponse|RedirectResponse
    {
        $data = $http->validate([
            'email' => ['required', 'string', 'max:255'],
            'purpose' => ['nullable', 'string', 'max:64'],
        ]);

        $subject = Subjects::findByEmail((string) $data['email']);

        if ($subject === null) {
            return back()->withErrors(['email' => 'No person with that email.']);
        }

        $purpose = isset($data['purpose']) && $data['purpose'] !== '' ? (string) $data['purpose'] : null;

        if ($purpose !== null && ! $this->dataRights->purposes()->has($purpose)) {
            return back()->withErrors(['purpose' => 'Unknown purpose.']);
        }

        return response()->json(
            $this->dataRights->proof($subject, $purpose),
            200,
            ['Content-Disposition' => 'attachment; filename="consent-proof-'.now()->format('Y-m-d').'.json"'],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }
}
