@extends('data-rights::layouts.desk')

@section('title', $breach->title)

@section('content')
    @php($D = \Vipertecpro\DataRightsDesk\Support\Dates::class)
    <p><a href="{{ route('data-rights-desk.breaches.index') }}">Back to breaches</a></p>
    <h1>{{ $breach->title }}</h1>

    <div class="drd-grid drd-grid--3">
        <div class="drd-stat drd-stat--key">
            <div class="drd-stat__label">Board report (72 hours)</div>
            @if ($breach->board_reported_at)
                <div class="drd-stat__value">Sent</div>
                <div class="drd-small">{{ $D::time($breach->board_reported_at) }}</div>
            @else
                <div @class(['drd-stat__value', 'drd-danger' => $breach->isReportOverdue()])>{{ $D::hours($breach->reportDueAt()) }}</div>
                <div class="drd-small">Due {{ $D::time($breach->reportDueAt()) }}</div>
            @endif
        </div>
        <div class="drd-stat">
            <div class="drd-stat__label">Board alerted</div>
            <div class="drd-stat__value">{{ $breach->board_alerted_at ? 'Yes' : 'No' }}</div>
            <div class="drd-small">{{ $breach->board_alerted_at ? $D::time($breach->board_alerted_at) : 'Send the alert without delay.' }}</div>
        </div>
        <div class="drd-stat">
            <div class="drd-stat__label">People told</div>
            <div class="drd-stat__value">{{ $notified }} / {{ $affected }}</div>
        </div>
    </div>

    <h2>Drafts</h2>
    <p class="drd-small drd-muted">The package drafts; you check every detail and send them yourself.</p>
    <div class="drd-actions">
        <a @class(['drd-button', 'drd-button--primary' => ! $breach->board_alerted_at]) href="{{ route('data-rights-desk.breaches.draft', [$breach, 'alert']) }}">Board alert draft</a>
        <a @class(['drd-button', 'drd-button--primary' => $breach->board_alerted_at && ! $breach->board_reported_at]) href="{{ route('data-rights-desk.breaches.draft', [$breach, 'report']) }}">Board report draft</a>
        @foreach ($locales as $locale)
            <a class="drd-button" href="{{ route('data-rights-desk.breaches.draft', [$breach, 'person', 'lang' => $locale]) }}">Message to people ({{ $locale }})</a>
        @endforeach
    </div>

    <h2>Steps</h2>
    <div class="drd-actions">
        @unless ($breach->board_alerted_at)
            <form method="POST" action="{{ route('data-rights-desk.breaches.board-alerted', $breach) }}" class="drd-inline">
                @csrf
                <button type="submit" class="drd-button">Mark: Board alerted</button>
            </form>
        @endunless
        @unless ($breach->board_reported_at)
            <form method="POST" action="{{ route('data-rights-desk.breaches.board-reported', $breach) }}" class="drd-inline">
                @csrf
                <button type="submit" class="drd-button">Mark: report sent</button>
            </form>
        @endunless
        @if ($affected > $notified)
            <form method="POST" action="{{ route('data-rights-desk.breaches.notify', $breach) }}" class="drd-inline">
                @csrf
                <button type="submit" class="drd-button">Tell {{ $affected - $notified }} {{ \Illuminate\Support\Str::plural('person', $affected - $notified) }} now</button>
            </form>
        @endif
    </div>

    <form method="POST" action="{{ route('data-rights-desk.breaches.affected', $breach) }}" class="drd-card">
        @csrf
        <h2>Add affected people</h2>
        <div class="drd-field">
            <label for="drd-emails">Emails, one per line</label>
            <textarea id="drd-emails" name="emails" required></textarea>
        </div>
        <button type="submit" class="drd-button">Add people</button>
    </form>

    <form method="POST" action="{{ route('data-rights-desk.breaches.update', $breach) }}" class="drd-card">
        @csrf
        @method('PUT')
        <h2>Details</h2>
        @include('data-rights::desk.breaches.fields', ['breach' => $breach])
        <button type="submit" class="drd-button">Save details</button>
    </form>
@endsection
