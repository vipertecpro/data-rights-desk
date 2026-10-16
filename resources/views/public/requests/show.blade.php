@extends('data-rights::layouts.public')

@section('title', __('data-rights::public.requests.show_title', ['type' => __('data-rights::public.types.'.$request->type->value)]))

@section('content')
    <p><a href="{{ route('data-rights.requests.index', $lang) }}">{{ __('data-rights::public.requests.back') }}</a></p>

    <h1>{{ __('data-rights::public.requests.show_title', ['type' => __('data-rights::public.types.'.$request->type->value)]) }}</h1>

    <dl class="drd-dl">
        <dt>{{ __('data-rights::public.requests.status') }}</dt>
        <dd><span class="drd-badge">{{ __('data-rights::public.statuses.'.$request->status->value) }}</span></dd>
        <dt>{{ __('data-rights::public.requests.received_label') }}</dt>
        <dd>{{ \Vipertecpro\DataRightsDesk\Support\Dates::day($request->received_at, $locale) }}</dd>
        @unless ($request->isClosed())
            <dt>{{ __('data-rights::public.requests.due_label') }}</dt>
            <dd>{{ \Vipertecpro\DataRightsDesk\Support\Dates::day($request->due_at, $locale) }}</dd>
        @endunless
        <dt>{{ __('data-rights::public.requests.reference_label') }}</dt>
        <dd>{{ $request->getKey() }}</dd>
    </dl>

    @if (filled($request->details))
        <h2>{{ __('data-rights::public.requests.details_heading') }}</h2>
        <p>{!! nl2br(e($request->details)) !!}</p>
    @endif

    @if (filled($request->response))
        <h2>{{ __('data-rights::public.requests.response_heading') }}</h2>
        <div class="drd-card">{!! nl2br(e($request->response)) !!}</div>
    @endif

    @if ($request->downloadable())
        <p class="drd-actions">
            <a class="drd-button drd-button--primary" href="{{ route('data-rights.requests.download', $request) }}">{{ __('data-rights::public.requests.download') }}</a>
            <span class="drd-muted drd-small">{{ __('data-rights::public.requests.download_until', ['date' => \Vipertecpro\DataRightsDesk\Support\Dates::day($request->closed_at?->copy()->addDays(\Vipertecpro\DataRightsDesk\Support\Limits::downloadDays()), $locale)]) }}</span>
        </p>
    @endif

    @unless ($request->isClosed())
        <form method="POST" action="{{ route('data-rights.requests.cancel', $request) }}">
            @csrf
            <input type="hidden" name="lang" value="{{ $locale }}">
            <button type="submit" class="drd-button">{{ __('data-rights::public.requests.cancel') }}</button>
        </form>
    @endunless

    <h2>{{ __('data-rights::public.rights.contact_heading') }}</h2>
    @include('data-rights::partials.contact')
@endsection
