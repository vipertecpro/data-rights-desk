<?php

namespace Vipertecpro\DataRightsDesk\Http\Controllers\Desk;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Vipertecpro\DataRightsDesk\DataRightsManager;
use Vipertecpro\DataRightsDesk\Enums\BreachDraftKind;
use Vipertecpro\DataRightsDesk\Models\Breach;
use Vipertecpro\DataRightsDesk\Support\Languages;
use Vipertecpro\DataRightsDesk\Support\Subjects;

class BreachController
{
    public function __construct(private DataRightsManager $dataRights) {}

    public function index(): View
    {
        return view('data-rights::desk.breaches.index', [
            'breaches' => Breach::query()->withCount([
                'affected',
                'affected as notified_count' => fn ($q) => $q->whereNotNull('notified_at'),
            ])->orderByDesc('became_aware_at')->paginate(50),
        ]);
    }

    public function create(): View
    {
        return view('data-rights::desk.breaches.create');
    }

    public function store(Request $http): RedirectResponse
    {
        $breach = $this->dataRights->breaches()->record($this->validated($http, true));

        return redirect()->route('data-rights-desk.breaches.show', $breach)->with('data-rights.status', 'Breach recorded. The report to the Board is due within 72 hours.');
    }

    public function show(Breach $breach): View
    {
        return view('data-rights::desk.breaches.show', [
            'breach' => $breach,
            'affected' => $breach->affected()->count(),
            'notified' => $breach->affected()->whereNotNull('notified_at')->count(),
            'locales' => Languages::offered(),
        ]);
    }

    public function update(Request $http, Breach $breach): RedirectResponse
    {
        $this->dataRights->breaches()->update($breach, $this->validated($http, false));

        return redirect()->route('data-rights-desk.breaches.show', $breach)->with('data-rights.status', 'Breach updated.');
    }

    /** Affected people, by email, one per line. */
    public function affected(Request $http, Breach $breach): RedirectResponse
    {
        $data = $http->validate(['emails' => ['required', 'string', 'max:200000']]);
        $emails = array_values(array_unique(array_filter(array_map('trim', preg_split('/\R/', (string) $data['emails']) ?: []))));
        $subjects = [];
        $unknown = 0;

        foreach ($emails as $email) {
            $subject = Subjects::findByEmail($email);

            if ($subject === null) {
                $unknown++;
            } else {
                $subjects[] = $subject;
            }
        }

        $added = $this->dataRights->breaches()->addAffected($breach, $subjects);
        $message = 'Added '.$added.' '.Str::plural('person', $added).'.'.($unknown > 0 ? " {$unknown} email(s) did not match anyone." : '');

        return redirect()->route('data-rights-desk.breaches.show', $breach)->with('data-rights.status', $message);
    }

    public function boardAlerted(Breach $breach): RedirectResponse
    {
        $this->dataRights->breaches()->markBoardAlerted($breach);

        return redirect()->route('data-rights-desk.breaches.show', $breach)->with('data-rights.status', 'Marked: the Board was alerted.');
    }

    public function boardReported(Breach $breach): RedirectResponse
    {
        $this->dataRights->breaches()->markBoardReported($breach);

        return redirect()->route('data-rights-desk.breaches.show', $breach)->with('data-rights.status', 'Marked: the report was sent to the Board.');
    }

    public function notify(Breach $breach): RedirectResponse
    {
        $sent = $this->dataRights->breaches()->notifyAffected($breach);

        return redirect()->route('data-rights-desk.breaches.show', $breach)->with('data-rights.status', 'Told '.$sent.' '.Str::plural('person', $sent).'.');
    }

    public function draft(Request $http, Breach $breach, string $kind): Response
    {
        $draftKind = BreachDraftKind::from($kind);
        $lang = $http->query('lang');
        $text = $this->dataRights->breaches()->draft($breach, $draftKind, is_string($lang) ? $lang : null);

        return response($text, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="breach-'.$kind.'-'.now()->format('Y-m-d').'.txt"',
        ]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $http, bool $creating): array
    {
        $text = ['nullable', 'string', 'max:20000'];

        return $http->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'nature' => [$creating ? 'required' : 'sometimes', 'string', 'max:20000'],
            'extent' => $text,
            'location' => $text,
            'likely_impact' => $text,
            'facts' => $text,
            'mitigation' => $text,
            'findings' => $text,
            'remedial' => $text,
            'consequences_for_people' => $text,
            'safety_steps' => $text,
            'occurred_at' => ['nullable', 'date'],
            'became_aware_at' => ['nullable', 'date'],
            'board_extension_until' => ['nullable', 'date'],
        ]);
    }
}
