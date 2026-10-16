@extends('data-rights::layouts.desk')

@section('title', 'Overview')

@section('content')
    @php($D = \Vipertecpro\DataRightsDesk\Support\Dates::class)
    <div class="drd-pagehead">
        <div>
            <h1>Overview</h1>
            <p class="drd-lead">Requests, breaches, consents and the checker at a glance.</p>
        </div>
        <a class="drd-button drd-button--primary" href="{{ route('data-rights-desk.requests.index') }}">See open requests</a>
    </div>

    <div class="drd-grid drd-grid--3">
        <div class="drd-stat drd-stat--key">
            <div class="drd-stat__label">Open requests</div>
            <div class="drd-stat__value">{{ $openRequests }}</div>
            <div class="drd-small">
                @if ($nearestDue)
                    Nearest: <span @class(['drd-danger' => $nearestDue->isPast()])>{{ $D::due($nearestDue) }}</span>
                @else
                    Nothing waiting.
                @endif
            </div>
        </div>
        <div class="drd-stat">
            <div class="drd-stat__label">Overdue requests</div>
            <div @class(['drd-stat__value', 'drd-danger' => $overdueRequests > 0])>{{ $overdueRequests }}</div>
            @if ($overdueRequests > 0)
                <a class="drd-small" href="{{ route('data-rights-desk.requests.index', ['overdue' => 1]) }}">See overdue requests</a>
            @endif
        </div>
        <div class="drd-stat">
            <div class="drd-stat__label">Checker errors</div>
            <div @class(['drd-stat__value', 'drd-danger' => ! $check->passed()])>{{ count($check->errors()) }}</div>
            <a class="drd-small" href="{{ route('data-rights-desk.check') }}">See the check ({{ count($check->warnings()) }} warnings)</a>
        </div>
    </div>

    <h2>Breaches waiting for the Board report</h2>
    @if ($openBreaches->isEmpty())
        <p class="drd-muted">None.</p>
    @else
        <ul class="drd-list">
            @foreach ($openBreaches as $breach)
                <li>
                    <a href="{{ route('data-rights-desk.breaches.show', $breach) }}">{{ $breach->title }}</a>:
                    <span @class(['drd-danger' => $breach->isReportOverdue()])>{{ $D::hours($breach->reportDueAt()) }}</span> to the 72-hour report
                </li>
            @endforeach
        </ul>
    @endif

    <h2>Consents per purpose</h2>
    <table class="drd-table">
        <thead>
            <tr><th scope="col">Purpose</th><th scope="col">Given</th><th scope="col">Withdrawn</th></tr>
        </thead>
        <tbody>
            @forelse ($purposes as $key => $row)
                <tr>
                    <td data-label="Purpose">{{ $row['title'] }} <span class="drd-muted drd-small">{{ $key }}</span></td>
                    <td data-label="Given">{{ $row['given'] }}</td>
                    <td data-label="Withdrawn">{{ $row['withdrawn'] }}</td>
                </tr>
            @empty
                <tr><td>No purposes configured.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="drd-grid drd-grid--2">
        <div class="drd-card">
            <h3>Notice</h3>
            <p>{{ $noticeVersion ? 'Current version: '.$noticeVersion : 'Not published yet.' }}</p>
            <a href="{{ route('data-rights-desk.notices.index') }}">Notices</a>
        </div>
        <div class="drd-card">
            <h3>Last retention run</h3>
            <p>{{ $lastRun ? $D::time($lastRun->started_at).($lastRun->pretend ? ' (pretend)' : '') : 'Never.' }}</p>
            <a href="{{ route('data-rights-desk.retention.index') }}">Retention</a>
        </div>
    </div>
@endsection
