<?php

namespace Vipertecpro\DataRightsDesk\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Vipertecpro\DataRightsDesk\Contracts\ParentalConsentVerifier;
use Vipertecpro\DataRightsDesk\DataRightsManager;
use Vipertecpro\DataRightsDesk\Enums\ConsentAction;
use Vipertecpro\DataRightsDesk\Enums\ConsentChannel;
use Vipertecpro\DataRightsDesk\Exceptions\NoticeMissing;
use Vipertecpro\DataRightsDesk\Http\Concerns\ResolvesPublicContext;
use Vipertecpro\DataRightsDesk\Models\SubjectProfile;

/**
 * s.6(1): consent per purpose, from an explicit request by the person.
 * s.6(4): withdraw on the same page, with the same one click.
 */
class PreferenceController
{
    use ResolvesPublicContext;

    public function __construct(private DataRightsManager $dataRights) {}

    public function show(Request $request): View
    {
        $locale = $this->pageLocale($request);
        $subject = $this->subject($request);
        $children = $this->dataRights->children();
        $kind = $children->guardianKindNeeded($subject);
        $isChild = $children->isChild($subject);
        $rows = [];

        foreach ($this->dataRights->purposes()->all() as $key => $purpose) {
            $latest = $this->dataRights->consents()->latest($subject, $key);

            $rows[] = [
                'purpose' => $purpose->toArray($locale),
                'allowed' => $this->dataRights->allows($subject, $key),
                'latest' => $latest?->action,
                'objectable' => $purpose->canBeObjectedTo(),
                'needs_consent' => $purpose->needsConsent(),
                'blocked_for_child' => $isChild && $purpose->tracking && ! $purpose->hasChildExemption(),
            ];
        }

        return view('data-rights::public.preferences', [
            'locale' => $locale,
            'rows' => $rows,
            'notice' => $this->dataRights->notices()->current($locale),
            'profile' => SubjectProfile::lookup($subject),
            'childrenEnabled' => $children->enabled(),
            'guardianKind' => $kind,
            'guardianConsent' => $kind !== null ? $children->activeGuardianConsent($subject, $kind) : null,
            'lang' => $this->langParameter($locale),
        ]);
    }

    public function give(Request $request, string $purpose): RedirectResponse
    {
        return $this->record($request, $purpose, ConsentAction::Given);
    }

    public function withdraw(Request $request, string $purpose): RedirectResponse
    {
        return $this->record($request, $purpose, ConsentAction::Withdrawn);
    }

    public function age(Request $request): RedirectResponse
    {
        $locale = $this->pageLocale($request);
        $subject = $this->subject($request);
        $data = $request->validate(['is_adult' => ['required', 'in:0,1']]);

        $this->dataRights->children()->recordAge($subject, $data['is_adult'] === '1' || $data['is_adult'] === 1);

        return redirect()->route('data-rights.preferences', $this->langParameter($locale))
            ->with('data-rights.status', (string) __('data-rights::public.preferences.age_saved'));
    }

    public function guardian(Request $request, ParentalConsentVerifier $verifier): RedirectResponse
    {
        $locale = $this->pageLocale($request);
        $subject = $this->subject($request);

        $this->dataRights->children()->recordGuardianConsent($subject, $verifier->verify($subject, $request));

        return redirect()->route('data-rights.preferences', $this->langParameter($locale))
            ->with('data-rights.status', (string) __('data-rights::public.preferences.guardian_saved'));
    }

    private function record(Request $request, string $key, ConsentAction $action): RedirectResponse
    {
        $locale = $this->pageLocale($request);
        $subject = $this->subject($request);

        abort_unless($this->dataRights->purposes()->has($key), 404);

        $purpose = $this->dataRights->purposes()->get($key);
        $back = redirect()->route('data-rights.preferences', $this->langParameter($locale));
        $options = [
            'channel' => ConsentChannel::Web,
            'locale' => $locale,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        if ($action === ConsentAction::Given && ! $purpose->hasChildExemption()) {
            $children = $this->dataRights->children();
            $kind = $children->guardianKindNeeded($subject);

            if ($kind !== null) {
                $guardianConsent = $children->activeGuardianConsent($subject, $kind);

                if ($guardianConsent === null) {
                    return $back->withErrors(['purpose' => (string) __('data-rights::public.preferences.guardian_first')]);
                }

                $options['given_by'] = $kind->givenBy();
                $options['guardian_consent'] = $guardianConsent;
            }
        }

        try {
            $action === ConsentAction::Given
                ? $this->dataRights->give($subject, $key, $options)
                : $this->dataRights->withdraw($subject, $key, $options);
        } catch (NoticeMissing) {
            return $back->withErrors(['purpose' => (string) __('data-rights::public.preferences.notice_missing')]);
        }

        $message = $action === ConsentAction::Given ? 'given' : 'withdrawn';

        return $back->with('data-rights.status', (string) __("data-rights::public.preferences.{$message}", ['purpose' => $purpose->title($locale)]));
    }
}
