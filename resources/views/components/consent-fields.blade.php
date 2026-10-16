@props([
    'purposes' => null,
    'required' => [],
    'locale' => null,
    'field' => 'data_rights',
    'askAge' => false,
])
@php
    /** @var \Vipertecpro\DataRightsDesk\DataRightsManager $drd */
    $drd = app(\Vipertecpro\DataRightsDesk\DataRightsManager::class);
    $drdLocale = \Vipertecpro\DataRightsDesk\Support\Languages::resolve($locale, request()->query('lang'), app()->getLocale());
    $drdNotice = $drd->notices()->current($drdLocale) ?? $drd->notices()->current();
    $drdLocale = $drdNotice?->locale ?? $drdLocale;
    $drdConsentBased = $drd->purposes()->consentBased();

    // A purpose named here that is not a consent purpose is a mistake in the form, not in the input: say so.
    foreach ([...(array) ($purposes ?? []), ...(array) $required] as $drdKey) {
        $drd->purposes()->get((string) $drdKey);

        if (! isset($drdConsentBased[$drdKey])) {
            throw new \InvalidArgumentException("Purpose [{$drdKey}] does not need consent, so it cannot be asked for in <x-data-rights::consent-fields>.");
        }
    }

    $drdPurposes = array_filter(
        $drdConsentBased,
        fn ($purpose, string $key): bool => $purposes === null || in_array($key, (array) $purposes, true),
        ARRAY_FILTER_USE_BOTH,
    );
    $drdOld = (array) old($field.'.purposes', []);
    $drdOldAge = old($field.'.age');
    $drdErrorKey = str_replace(['[', ']'], ['.', ''], $field);
    // $errors is shared by the web middleware; outside it there are none to show.
    $drdErrors = isset($errors) && $errors instanceof \Illuminate\Support\ViewErrorBag ? $errors : new \Illuminate\Support\ViewErrorBag;
@endphp
{{-- s.5(1) notice with or before the request; s.6(1) one box per purpose, none ticked for the person. --}}
<fieldset {{ $attributes->merge(['class' => 'drd-consent-fields', 'lang' => $drdLocale]) }}>
    <legend>{{ __('data-rights::public.signup.legend', [], $drdLocale) }}</legend>

    @if ($drdNotice === null)
        <p>{{ __('data-rights::public.signup.no_notice', [], $drdLocale) }}</p>
    @else
        @if ($askAge)
            {{-- s.9(1): know whether the person is a child before anything is recorded for her. --}}
            <p class="drd-consent-age">
                {{ __('data-rights::public.preferences.age_question', [], $drdLocale) }}
                <label><input type="radio" name="{{ $field }}[age]" value="adult" @checked($drdOldAge === 'adult') required> {{ __('data-rights::public.preferences.age_adult', [], $drdLocale) }}</label>
                <label><input type="radio" name="{{ $field }}[age]" value="child" @checked($drdOldAge === 'child')> {{ __('data-rights::public.preferences.age_child', [], $drdLocale) }}</label>
            </p>
            @foreach ($drdErrors->get($drdErrorKey.'.age') as $drdMessage)
                <p class="drd-consent-error" role="alert">{{ $drdMessage }}</p>
            @endforeach
        @endif

        <p>
            {{ __('data-rights::public.signup.intro', [], $drdLocale) }}
            @if (Route::has('data-rights.notice.version'))
                <a href="{{ route('data-rights.notice.version', ['version' => $drdNotice->version, 'lang' => $drdNotice->locale]) }}" target="_blank" rel="noopener">{{ __('data-rights::public.signup.notice_link', [], $drdLocale) }}</a>
            @endif
        </p>

        @foreach ($drdPurposes as $key => $purpose)
            <label class="drd-consent-field">
                <input type="checkbox" name="{{ $field }}[purposes][]" value="{{ $key }}" @checked(in_array($key, $drdOld, true)) @required(in_array($key, (array) $required, true))>
                <span>
                    <strong>{{ $purpose->title($drdLocale) }}</strong>
                    <span>{{ $purpose->description($drdLocale) }}</span>
                    <small>{{ __('data-rights::public.signup.data_used', [], $drdLocale) }} {{ implode(', ', $purpose->data($drdLocale)) }}</small>
                </span>
            </label>
        @endforeach

        @foreach ($drdErrors->get($drdErrorKey.'.purposes') as $drdMessage)
            <p class="drd-consent-error" role="alert">{{ $drdMessage }}</p>
        @endforeach

        <p><small>{{ __('data-rights::public.signup.withdraw', [], $drdLocale) }}</small></p>
        <input type="hidden" name="{{ $field }}[notice]" value="{{ $drdNotice->getKey() }}">
        <input type="hidden" name="{{ $field }}[locale]" value="{{ $drdNotice->locale }}">
    @endif
</fieldset>
