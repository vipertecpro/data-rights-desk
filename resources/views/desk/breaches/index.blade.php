@extends('data-rights::layouts.desk')

@section('title', 'Breaches')

@section('content')
    @php($D = \Vipertecpro\DataRightsDesk\Support\Dates::class)
    <div class="drd-pagehead">
        <div>
            <h1>Breaches</h1>
            <p class="drd-lead">Rule 7: tell the Board without delay, send the detailed report within 72 hours, and tell each affected person.</p>
        </div>
        <a class="drd-button drd-button--primary" href="{{ route('data-rights-desk.breaches.create') }}">Record a breach</a>
    </div>

    @if ($breaches->isEmpty())
        <p class="drd-muted">No breaches recorded.</p>
    @else
        <table class="drd-table">
            <thead>
                <tr><th scope="col">Breach</th><th scope="col">Board report</th><th scope="col">Board alerted</th><th scope="col">People told</th></tr>
            </thead>
            <tbody>
                @foreach ($breaches as $breach)
                    <tr>
                        <td data-label="Breach"><a href="{{ route('data-rights-desk.breaches.show', $breach) }}">{{ $breach->title }}</a><span class="drd-help">Aware {{ $D::time($breach->became_aware_at) }}</span></td>
                        <td data-label="Board report">
                            @if ($breach->board_reported_at)
                                <span class="drd-badge drd-badge--ok">Sent {{ $D::day($breach->board_reported_at) }}</span>
                            @else
                                <span @class(['drd-danger' => $breach->isReportOverdue()])>{{ $D::hours($breach->reportDueAt()) }}</span>
                            @endif
                        </td>
                        <td data-label="Board alerted">{{ $breach->board_alerted_at ? $D::time($breach->board_alerted_at) : 'Not yet' }}</td>
                        <td data-label="People told">{{ $breach->notified_count }} of {{ $breach->affected_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
