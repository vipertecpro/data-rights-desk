<?php

namespace Vipertecpro\DataRightsDesk\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Vipertecpro\DataRightsDesk\DataRightsManager;
use Vipertecpro\DataRightsDesk\Http\Concerns\ResolvesPublicContext;
use Vipertecpro\DataRightsDesk\Models\Nomination;

/** s.14, Rule 14(4). */
class NominationController
{
    use ResolvesPublicContext;

    public function __construct(private DataRightsManager $dataRights) {}

    public function store(Request $request): RedirectResponse
    {
        $locale = $this->pageLocale($request);
        $subject = $this->subject($request);
        $data = $request->validate([
            'nominee_name' => ['required', 'string', 'max:200'],
            'nominee_contact' => ['required', 'string', 'max:200'],
            'relationship' => ['nullable', 'string', 'max:100'],
        ]);

        $this->dataRights->nominations()->nominate(
            $subject,
            (string) $data['nominee_name'],
            (string) $data['nominee_contact'],
            isset($data['relationship']) ? (string) $data['relationship'] : null,
        );

        return redirect()->route('data-rights.requests.index', $this->langParameter($locale))
            ->with('data-rights.status', (string) __('data-rights::public.nominations.saved'));
    }

    public function destroy(Request $request, Nomination $nomination): RedirectResponse
    {
        $locale = $this->pageLocale($request);
        $this->ensureOwn($nomination, $this->subject($request));

        $this->dataRights->nominations()->revoke($nomination);

        return redirect()->route('data-rights.requests.index', $this->langParameter($locale))
            ->with('data-rights.status', (string) __('data-rights::public.nominations.revoked'));
    }
}
