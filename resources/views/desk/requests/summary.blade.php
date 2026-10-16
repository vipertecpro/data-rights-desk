@extends('data-rights::layouts.desk')

@section('title', 'Access summary')

@section('content')
    <p><a href="{{ route('data-rights-desk.requests.show', $request) }}">Back to the request</a></p>
    <h1>Access summary preview</h1>
    <p class="drd-lead">What the person downloads after you complete the request (s.11). Generated now from your Exporters, the processing logs, the consent ledger and the processor register.</p>

    @if ($summary === null)
        <p class="drd-muted">This request is not linked to a person.</p>
    @else
        <pre class="drd-pre">{{ json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
    @endif
@endsection
