@extends('data-rights::layouts.desk')

@section('title', 'Retention')

@section('content')
    @php($D = \Vipertecpro\DataRightsDesk\Support\Dates::class)
    <h1>Retention</h1>
    <p class="drd-lead">
        Logs and package records are kept {{ $keepDays }} days (at least one year, Rule 8(3)), then php artisan data-rights:retain erases them.
        Inactivity erasure (Rule 8(1), Third Schedule) is {{ $inactivity ? 'on' : 'off' }}. Legal holds stop erasure.
    </p>

    <form method="POST" action="{{ route('data-rights-desk.retention.holds.store') }}" class="drd-card">
        @csrf
        <h2>Place a legal hold</h2>
        <div class="drd-field">
            <label for="drd-hold-email">Person's email</label>
            <input id="drd-hold-email" type="email" name="email" required maxlength="255" value="{{ old('email') }}" autocomplete="off">
        </div>
        <div class="drd-field">
            <label for="drd-hold-reason">Reason</label>
            <textarea id="drd-hold-reason" name="reason" required maxlength="2000">{{ old('reason') }}</textarea>
            <span class="drd-help">Stored encrypted.</span>
        </div>
        <div class="drd-grid drd-grid--2">
            <div class="drd-field">
                <label for="drd-hold-law">Law that needs the data kept</label>
                <input id="drd-hold-law" type="text" name="law_reference" maxlength="255" value="{{ old('law_reference') }}">
            </div>
            <div class="drd-field">
                <label for="drd-hold-until">Until (optional)</label>
                <input id="drd-hold-until" type="date" name="until" value="{{ old('until') }}">
            </div>
        </div>
        <button type="submit" class="drd-button drd-button--primary">Place hold</button>
    </form>

    <form method="POST" action="{{ route('data-rights-desk.retention.contact') }}" class="drd-card">
        @csrf
        <h2>Record contact from a person</h2>
        <p class="drd-small">Rule 8(2): a person who calls, writes or emails you has approached you, just like signing in. Recording it clears a pending erasure warning.</p>
        <div class="drd-field">
            <label for="drd-contact-email">Person's email</label>
            <input id="drd-contact-email" type="email" name="email" required maxlength="255" autocomplete="off">
        </div>
        <button type="submit" class="drd-button">Record contact</button>
    </form>

    <h2>Legal holds</h2>
    @if ($holds->isEmpty())
        <p class="drd-muted">No legal holds.</p>
    @else
        <table class="drd-table">
            <thead>
                <tr><th scope="col">Record</th><th scope="col">Reason</th><th scope="col">Placed</th><th scope="col">State</th></tr>
            </thead>
            <tbody>
                @foreach ($holds as $hold)
                    <tr>
                        <td data-label="Record">
                            @php($holdable = $hold->holdable)
                            {{ $holdable ? \Vipertecpro\DataRightsDesk\Support\Subjects::label($holdable) : class_basename($hold->holdable_type).' '.$hold->holdable_id }}
                        </td>
                        <td data-label="Reason">{{ $hold->reason }}@if ($hold->law_reference)<span class="drd-help">{{ $hold->law_reference }}</span>@endif</td>
                        <td data-label="Placed">{{ $D::day($hold->placed_at) }}@if ($hold->expires_at)<span class="drd-help">until {{ $D::day($hold->expires_at) }}</span>@endif</td>
                        <td data-label="State">
                            @if ($hold->isActive())
                                <form method="POST" action="{{ route('data-rights-desk.retention.holds.release', $hold) }}" class="drd-inline">
                                    @csrf
                                    <button type="submit" class="drd-button">Release</button>
                                </form>
                            @else
                                <span class="drd-muted">{{ $hold->released_at ? 'Released '.$D::day($hold->released_at) : 'Expired' }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Runs</h2>
    @if ($runs->isEmpty())
        <p class="drd-muted">The keeper has not run yet. Schedule php artisan data-rights:retain daily.</p>
    @else
        <table class="drd-table">
            <thead>
                <tr><th scope="col">Started</th><th scope="col">Counts</th></tr>
            </thead>
            <tbody>
                @foreach ($runs as $run)
                    <tr>
                        <td data-label="Started">{{ $D::time($run->started_at) }}{{ $run->pretend ? ' (pretend)' : '' }}</td>
                        <td data-label="Counts">
                            @foreach ((array) ($run->summary['counts'] ?? []) as $step => $count)
                                {{ str_replace('_', ' ', $step) }}: {{ $count }}@if (! $loop->last) · @endif
                            @endforeach
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
