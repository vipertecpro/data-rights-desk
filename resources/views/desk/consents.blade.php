@extends('data-rights::layouts.desk')

@section('title', 'Consents')

@section('content')
    <h1>Consents</h1>
    <p class="drd-lead">Ledger rows per purpose. Look up one person to see their history and download the proof (s.6(10)).</p>

    <table class="drd-table">
        <thead>
            <tr><th scope="col">Purpose</th><th scope="col">Given</th><th scope="col">Withdrawn</th></tr>
        </thead>
        <tbody>
            @foreach ($purposes as $key => $row)
                <tr>
                    <td data-label="Purpose">{{ $row['title'] }} <span class="drd-muted drd-small">{{ $key }}</span></td>
                    <td data-label="Given">{{ $row['given'] }}</td>
                    <td data-label="Withdrawn">{{ $row['withdrawn'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <form method="POST" action="{{ route('data-rights-desk.consents.lookup') }}" class="drd-filters">
        @csrf
        <div class="drd-field">
            <label for="drd-lookup">Person's email</label>
            <input id="drd-lookup" type="search" name="email" value="{{ $email }}" maxlength="255" autocomplete="off">
        </div>
        <button type="submit" @class(['drd-button', 'drd-button--primary' => ! $subject])>Find</button>
    </form>

    @if ($email !== '' && ! $subject)
        <p class="drd-muted">No person with that email.</p>
    @endif

    @if ($subject)
        <h2>{{ \Vipertecpro\DataRightsDesk\Support\Subjects::label($subject) }}</h2>
        <ul class="drd-list">
            @foreach ($current as $key => $allowed)
                <li>{{ isset($allPurposes[$key]) ? $allPurposes[$key]->title() : $key }}: <span @class(['drd-badge', 'drd-badge--ok' => $allowed])>{{ $allowed ? 'Allowed' : 'Not allowed' }}</span></li>
            @endforeach
        </ul>

        <table class="drd-table">
            <thead>
                <tr><th scope="col">When</th><th scope="col">Purpose</th><th scope="col">Action</th><th scope="col">Channel</th><th scope="col">Notice</th></tr>
            </thead>
            <tbody>
                @forelse ($history as $record)
                    <tr>
                        <td data-label="When">{{ \Vipertecpro\DataRightsDesk\Support\Dates::time($record->occurred_at) }}</td>
                        <td data-label="Purpose">{{ $record->purpose }}</td>
                        <td data-label="Action">{{ $record->action->value }}{{ $record->given_by->value !== 'self' ? ' by '.str_replace('_', ' ', $record->given_by->value) : '' }}</td>
                        <td data-label="Channel">{{ $record->channel->value }}</td>
                        <td data-label="Notice">{{ $record->notice_hash ? substr($record->notice_hash, 0, 12) : 'none' }}</td>
                    </tr>
                @empty
                    <tr><td>No ledger rows.</td></tr>
                @endforelse
            </tbody>
        </table>

        <form method="POST" action="{{ route('data-rights-desk.consents.proof') }}" class="drd-card">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <div class="drd-field">
                <label for="drd-proof-purpose">Purpose</label>
                <select id="drd-proof-purpose" name="purpose">
                    <option value="">All purposes</option>
                    @foreach ($allPurposes as $key => $purpose)
                        <option value="{{ $key }}">{{ $purpose->title() }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="drd-button drd-button--primary">Download proof (JSON)</button>
        </form>
    @endif
@endsection
