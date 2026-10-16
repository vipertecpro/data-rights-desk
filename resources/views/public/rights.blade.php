@extends('data-rights::layouts.public')

@section('title', __('data-rights::public.rights.title'))

@section('content')
    <h1>{{ __('data-rights::public.rights.title') }}</h1>
    <p class="drd-lead">{{ __('data-rights::public.rights.intro', ['business' => \Vipertecpro\DataRightsDesk\Support\Contact::businessName()]) }}</p>

    <h2>{{ __('data-rights::public.rights.list_heading') }}</h2>
    <ul class="drd-list">
        <li>{{ __('data-rights::public.rights.right_access') }}</li>
        <li>{{ __('data-rights::public.rights.right_correction') }}</li>
        <li>{{ __('data-rights::public.rights.right_erasure') }}</li>
        <li>{{ __('data-rights::public.rights.right_grievance') }}</li>
        <li>{{ __('data-rights::public.rights.right_nomination') }}</li>
        <li>{{ __('data-rights::public.rights.right_withdraw') }}</li>
    </ul>

    <h2>{{ __('data-rights::public.rights.how_heading') }}</h2>
    <p>{{ \Vipertecpro\DataRightsDesk\View\Html::withLink('data-rights::public.rights.how_signed_in', route('data-rights.requests.index', $lang)) }}</p>
    <p>{{ __('data-rights::public.rights.how_contact') }}</p>
    <p class="drd-actions">
        <a class="drd-button drd-button--primary" href="{{ route('data-rights.requests.index', $lang) }}">{{ __('data-rights::public.rights.requests_link') }}</a>
    </p>

    <h2>{{ __('data-rights::public.rights.identifier_heading') }}</h2>
    <p>{{ __('data-rights::public.rights.identifier_body', ['identifier' => $identifier]) }}</p>

    <h2>{{ __('data-rights::public.rights.time_heading') }}</h2>
    <p>{{ __('data-rights::public.rights.time_body', ['days' => $days]) }}</p>

    <h2>{{ __('data-rights::public.rights.board_heading') }}</h2>
    @if ($boardUrl)
        <p>{{ \Vipertecpro\DataRightsDesk\View\Html::withLink('data-rights::public.rights.board_with_url', $boardUrl) }}</p>
    @else
        <p>{{ __('data-rights::public.rights.board_without_url') }}</p>
    @endif

    <h2>{{ __('data-rights::public.rights.contact_heading') }}</h2>
    @include('data-rights::partials.contact')

    <h2>{{ __('data-rights::public.rights.links_heading') }}</h2>
    <ul class="drd-list">
        <li><a href="{{ route('data-rights.notice', $lang) }}">{{ __('data-rights::public.rights.notice_link') }}</a></li>
        <li><a href="{{ route('data-rights.preferences', $lang) }}">{{ __('data-rights::public.rights.preferences_link') }}</a></li>
    </ul>
@endsection
