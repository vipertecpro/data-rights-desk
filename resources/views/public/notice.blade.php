@extends('data-rights::layouts.public')

@section('title', __('data-rights::public.notice.title'))

@section('content')
    @if ($older)
        <p class="drd-alert drd-alert--warn">
            <a href="{{ route('data-rights.notice', $lang) }}">{{ __('data-rights::public.notice.older', ['version' => $notice->version]) }}</a>
        </p>
    @endif

    @if ($notice)
        <article class="drd-prose" lang="{{ $notice->locale }}" dir="{{ \Vipertecpro\DataRightsDesk\Support\Languages::direction($notice->locale) }}">
            {!! $notice->html() !!}
        </article>

        <p class="drd-actions">
            <a class="drd-button drd-button--primary" href="{{ route('data-rights.preferences', $lang) }}">{{ __('data-rights::public.rights.preferences_link') }}</a>
        </p>
    @else
        <h1>{{ __('data-rights::public.notice.title') }}</h1>
        <p>{{ __('data-rights::public.notice.not_published') }}</p>
    @endif
@endsection
