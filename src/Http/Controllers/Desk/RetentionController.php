<?php

namespace Vipertecpro\DataRightsDesk\Http\Controllers\Desk;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Vipertecpro\DataRightsDesk\DataRightsManager;
use Vipertecpro\DataRightsDesk\Models\LegalHold;
use Vipertecpro\DataRightsDesk\Models\RetentionRun;
use Vipertecpro\DataRightsDesk\Support\Limits;
use Vipertecpro\DataRightsDesk\Support\Subjects;

class RetentionController
{
    public function __construct(private DataRightsManager $dataRights) {}

    public function index(): View
    {
        return view('data-rights::desk.retention', [
            'runs' => RetentionRun::query()->orderByDesc('started_at')->limit(20)->get(),
            'holds' => LegalHold::query()->orderByDesc('placed_at')->limit(100)->get(),
            'keepDays' => Limits::keepDays(),
            'inactivity' => (bool) config('data-rights.retention.inactivity.enabled', false),
        ]);
    }

    /** Places a legal hold on a person, found by email. */
    public function storeHold(Request $http): RedirectResponse
    {
        $data = $http->validate([
            'email' => ['required', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:2000'],
            'law_reference' => ['nullable', 'string', 'max:255'],
            'until' => ['nullable', 'date', 'after:today'],
        ]);

        $subject = Subjects::findByEmail((string) $data['email']);

        if ($subject === null) {
            return back()->withInput()->withErrors(['email' => 'No person with that email.']);
        }

        $user = $http->user();

        $this->dataRights->retention()->placeHold(
            $subject,
            (string) $data['reason'],
            isset($data['law_reference']) ? (string) $data['law_reference'] : null,
            isset($data['until']) ? Carbon::parse((string) $data['until'])->endOfDay() : null,
            $user instanceof Model ? $user : null,
        );

        return redirect()->route('data-rights-desk.retention.index')->with('data-rights.status', 'Legal hold placed.');
    }

    public function releaseHold(LegalHold $hold): RedirectResponse
    {
        $this->dataRights->retention()->releaseHold($hold);

        return redirect()->route('data-rights-desk.retention.index')->with('data-rights.status', 'Legal hold released.');
    }
}
