@php($drdOffered = \Vipertecpro\DataRightsDesk\Support\Languages::offered())
@if (count($drdOffered) > 1)
    <nav aria-label="{{ __('data-rights::public.layout.language') }}">
        <ul class="drd-langs">
            <li>{{ __('data-rights::public.layout.language') }}:</li>
            @foreach ($drdOffered as $drdCode)
                <li>
                    <a href="{{ request()->fullUrlWithQuery(['lang' => $drdCode]) }}"
                       lang="{{ $drdCode }}"
                       hreflang="{{ $drdCode }}"
                       dir="{{ \Vipertecpro\DataRightsDesk\Support\Languages::direction($drdCode) }}"
                       @if ($drdCode === app()->getLocale()) aria-current="true" @endif>{{ \Vipertecpro\DataRightsDesk\Support\Languages::nativeName($drdCode) }}</a>
                </li>
            @endforeach
        </ul>
    </nav>
@endif
