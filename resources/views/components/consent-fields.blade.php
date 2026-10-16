@props([
    'purposes' => null,
    'required' => [],
    'locale' => null,
    'field' => 'data_rights',
])
@php
    /** @var \Vipertecpro\DataRightsDesk\DataRightsManager $drd */
    $drd = app(\Vipertecpro\DataRightsDesk\DataRightsManager::class);
    $drdLocale = \Vipertecpro\DataRightsDesk\Support\Languages::resolve($locale, request()->query('lang'), app()->getLocale());
    $drdNotice = $drd->notices()->current($drdLocale) ?? $drd->notices()->current();
    $drdPurposes = array_filter(
        $drd->purposes()->consentBased(),
        fn ($purpose, string $key): bool => $purposes === null || in_array($key, (array) $purposes, true),
        ARRAY_FILTER_USE_BOTH,
    );
    $drdOld = (array) old($field.'.purposes', []);
@endphp
{{-- s.5(1) notice with or before the request; s.6(1) one box per purpose, none ticked for the person. --}}
<fieldset {{ $attributes->merge(['class' => 'drd-consent-fields']) }}>
    <legend>{{ __('data-rights::public.signup.legend') }}</legend>

    @if ($drdNotice === null)
        <p>{{ __('data-rights::public.signup.no_notice') }}</p>
    @else
        <p>
            {{ __('data-rights::public.signup.intro') }}
            @if (Route::has('data-rights.notice.version'))
                <a href="{{ route('data-rights.notice.version', ['version' => $drdNotice->version, 'lang' => $drdNotice->locale]) }}" target="_blank" rel="noopener">{{ __('data-rights::public.signup.notice_link') }}</a>
            @endif
        </p>

        @foreach ($drdPurposes as $key => $purpose)
            <label class="drd-consent-field">
                <input type="checkbox" name="{{ $field }}[purposes][]" value="{{ $key }}" @checked(in_array($key, $drdOld, true)) @required(in_array($key, (array) $required, true))>
                <span>
                    <strong>{{ $purpose->title($drdNotice->locale) }}</strong>
                    <span>{{ $purpose->description($drdNotice->locale) }}</span>
                    <small>{{ __('data-rights::public.signup.data_used') }} {{ implode(', ', $purpose->data($drdNotice->locale)) }}</small>
                </span>
            </label>
        @endforeach

        <p><small>{{ __('data-rights::public.signup.withdraw') }}</small></p>
        <input type="hidden" name="{{ $field }}[notice]" value="{{ $drdNotice->getKey() }}">
        <input type="hidden" name="{{ $field }}[locale]" value="{{ $drdNotice->locale }}">
    @endif
</fieldset>
