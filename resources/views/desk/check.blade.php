@extends('data-rights::layouts.desk')

@section('title', 'Check')

@section('content')
    <div class="drd-pagehead">
        <div>
            <h1>Check</h1>
            <p class="drd-lead">{{ count($report->errors()) }} errors, {{ count($report->warnings()) }} warnings. The checker looks at what this package can see. It is not legal advice.</p>
        </div>
        <a class="drd-button drd-button--primary" href="{{ route('data-rights-desk.check') }}">Run again</a>
    </div>

    <h2>Errors</h2>
    @forelse ($report->errors() as $item)
        <p class="drd-alert drd-alert--danger">{{ $item['message'] }} <span class="drd-small">({{ $item['code'] }})</span></p>
    @empty
        <p class="drd-alert drd-alert--ok">No errors.</p>
    @endforelse

    <h2>Warnings</h2>
    @forelse ($report->warnings() as $item)
        <p class="drd-alert drd-alert--warn">{{ $item['message'] }} <span class="drd-small">({{ $item['code'] }})</span></p>
    @empty
        <p class="drd-muted">No warnings.</p>
    @endforelse

    <p class="drd-small drd-muted">The same check runs with php artisan data-rights:check.</p>
@endsection
