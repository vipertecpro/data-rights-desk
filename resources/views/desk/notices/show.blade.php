@extends('data-rights::layouts.desk')

@section('title', 'Notice version '.$notice->version)

@section('content')
    <p><a href="{{ route('data-rights-desk.notices.index') }}">Back to notices</a></p>
    <h1>Notice version {{ $notice->version }} ({{ $notice->locale }})</h1>

    <dl class="drd-dl">
        <dt>Published</dt>
        <dd>{{ \Vipertecpro\DataRightsDesk\Support\Dates::time($notice->published_at) }}</dd>
        <dt>SHA-256 of the body</dt>
        <dd><code>{{ $notice->hash }}</code> {!! $notice->hashMatches() ? '<span class="drd-badge drd-badge--ok">matches</span>' : '<span class="drd-badge drd-badge--danger">does not match</span>' !!}</dd>
    </dl>

    <article class="drd-card drd-prose" lang="{{ $notice->locale }}" dir="{{ \Vipertecpro\DataRightsDesk\Support\Languages::direction($notice->locale) }}">
        {!! $notice->html() !!}
    </article>

    <h2>Markdown</h2>
    <pre class="drd-pre">{{ $notice->document() }}</pre>
@endsection
