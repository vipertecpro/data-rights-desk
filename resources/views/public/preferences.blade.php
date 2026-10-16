@extends('data-rights::layouts.public')

@section('title', __('data-rights::public.preferences.title'))

@section('content')
    <h1>{{ __('data-rights::public.preferences.title') }}</h1>
    <p class="drd-lead">{{ __('data-rights::public.preferences.intro') }}</p>

    @if ($notice)
        <p><a href="{{ route('data-rights.notice.version', ['version' => $notice->version, ...$lang]) }}">{{ __('data-rights::public.preferences.notice_link', ['version' => $notice->version]) }}</a></p>
    @endif

    @if ($childrenEnabled)
        <section class="drd-card" aria-labelledby="drd-age">
            <h2 id="drd-age">{{ __('data-rights::public.preferences.age_heading') }}</h2>
            @if ($profile?->age_status === null)
                <p>{{ __('data-rights::public.preferences.age_question') }}</p>
                <div class="drd-actions">
                    <form method="POST" action="{{ route('data-rights.age') }}" class="drd-inline">
                        @csrf
                        <input type="hidden" name="lang" value="{{ $locale }}">
                        <input type="hidden" name="is_adult" value="1">
                        <button type="submit" class="drd-button">{{ __('data-rights::public.preferences.age_adult') }}</button>
                    </form>
                    <form method="POST" action="{{ route('data-rights.age') }}" class="drd-inline">
                        @csrf
                        <input type="hidden" name="lang" value="{{ $locale }}">
                        <input type="hidden" name="is_adult" value="0">
                        <button type="submit" class="drd-button">{{ __('data-rights::public.preferences.age_child') }}</button>
                    </form>
                </div>
            @else
                <p>
                    {{ $profile->age_status === \Vipertecpro\DataRightsDesk\Enums\AgeStatus::Adult ? __('data-rights::public.preferences.age_is_adult') : __('data-rights::public.preferences.age_is_child') }}
                    <span class="drd-muted">{{ __('data-rights::public.preferences.age_change') }}</span>
                </p>
            @endif
        </section>
    @endif

    @if ($guardianKind)
        <section class="drd-card" aria-labelledby="drd-guardian">
            <h2 id="drd-guardian">{{ __('data-rights::public.preferences.guardian_heading') }}</h2>
            @if ($guardianConsent)
                <p>{{ __('data-rights::public.preferences.guardian_active', ['date' => \Vipertecpro\DataRightsDesk\Support\Dates::day($guardianConsent->verified_at, $locale)]) }}</p>
                <p>{{ __('data-rights::public.preferences.guardian_gives') }}</p>
            @else
                <p>{{ __('data-rights::public.preferences.guardian_needed') }}</p>
                <form method="POST" action="{{ route('data-rights.guardian') }}">
                    @csrf
                    <input type="hidden" name="lang" value="{{ $locale }}">
                    <button type="submit" class="drd-button">{{ __('data-rights::public.preferences.guardian_button') }}</button>
                </form>
            @endif
        </section>
    @endif

    @foreach ($rows as $row)
        @php($purpose = $row['purpose'])
        <section class="drd-card drd-purpose" aria-labelledby="drd-purpose-{{ $purpose['key'] }}">
            <div class="drd-purpose__body">
                <h2 id="drd-purpose-{{ $purpose['key'] }}">{{ $purpose['title'] }}</h2>
                @if ($purpose['description'] !== '')
                    <p>{{ $purpose['description'] }}</p>
                @endif
                <p class="drd-small"><strong>{{ __('data-rights::public.preferences.data_used') }}</strong> {{ implode(', ', $purpose['data']) }}</p>
                @if ($purpose['tracking'])
                    <p class="drd-small drd-muted">{{ __('data-rights::public.preferences.tracking') }}</p>
                @endif

                @if ($row['needs_consent'])
                    <p class="drd-purpose__state">
                        @if ($row['allowed'])
                            {{ __('data-rights::public.preferences.status_given') }}
                        @elseif ($row['latest'] === \Vipertecpro\DataRightsDesk\Enums\ConsentAction::Withdrawn)
                            {{ __('data-rights::public.preferences.status_withdrawn') }}
                        @else
                            {{ __('data-rights::public.preferences.status_not_given') }}
                        @endif
                    </p>
                    @if ($purpose['withdrawal_effect'])
                        <p class="drd-small">{{ __('data-rights::public.preferences.withdraw_effect', ['effect' => $purpose['withdrawal_effect']]) }}</p>
                    @endif
                @elseif ($row['objectable'])
                    <p class="drd-small">{{ __('data-rights::public.preferences.legitimate_use_a') }}</p>
                    @if ($row['latest'] === \Vipertecpro\DataRightsDesk\Enums\ConsentAction::Withdrawn)
                        <p class="drd-purpose__state">{{ __('data-rights::public.preferences.status_objected') }}</p>
                    @endif
                @else
                    <p class="drd-small">{{ __('data-rights::public.preferences.legitimate_use') }}</p>
                @endif
            </div>

            <div class="drd-purpose__action">
                @if ($row['needs_consent'] && $row['blocked_for_child'])
                    <p class="drd-muted">{{ __('data-rights::public.preferences.not_for_children') }}</p>
                @elseif ($row['needs_consent'])
                    @if ($row['allowed'])
                        <form method="POST" action="{{ route('data-rights.consents.withdraw', $purpose['key']) }}">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="lang" value="{{ $locale }}">
                            <button type="submit" class="drd-button">{{ __('data-rights::public.preferences.withdraw') }}</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('data-rights.consents.give', $purpose['key']) }}">
                            @csrf
                            <input type="hidden" name="lang" value="{{ $locale }}">
                            <button type="submit" class="drd-button">{{ __('data-rights::public.preferences.give') }}</button>
                        </form>
                    @endif
                @elseif ($row['objectable'])
                    @if ($row['latest'] === \Vipertecpro\DataRightsDesk\Enums\ConsentAction::Withdrawn)
                        <form method="POST" action="{{ route('data-rights.consents.give', $purpose['key']) }}">
                            @csrf
                            <input type="hidden" name="lang" value="{{ $locale }}">
                            <button type="submit" class="drd-button">{{ __('data-rights::public.preferences.allow_again') }}</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('data-rights.consents.withdraw', $purpose['key']) }}">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="lang" value="{{ $locale }}">
                            <button type="submit" class="drd-button">{{ __('data-rights::public.preferences.object') }}</button>
                        </form>
                    @endif
                @endif
            </div>
        </section>
    @endforeach

    <h2>{{ __('data-rights::public.preferences.contact_heading') }}</h2>
    @include('data-rights::partials.contact')
@endsection
