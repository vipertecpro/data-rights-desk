@extends('data-rights::layouts.desk')

@section('title', 'Requests')

@section('content')
    <div class="drd-pagehead">
        <div>
            <h1>Requests</h1>
            <p class="drd-lead">Every request has a 90-day ceiling (Rule 14(3)). Open requests come first, nearest due date first.</p>
        </div>
        <a class="drd-button drd-button--primary" href="{{ route('data-rights-desk.requests.create') }}">Record a request</a>
    </div>

    <form method="GET" action="{{ route('data-rights-desk.requests.index') }}" class="drd-filters">
        <div class="drd-field">
            <label for="drd-f-status">Status</label>
            <select id="drd-f-status" name="status">
                <option value="">Any</option>
                @foreach (\Vipertecpro\DataRightsDesk\Enums\RequestStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? null) === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="drd-field">
            <label for="drd-f-type">Type</label>
            <select id="drd-f-type" name="type">
                <option value="">Any</option>
                @foreach (\Vipertecpro\DataRightsDesk\Enums\RequestType::cases() as $type)
                    <option value="{{ $type->value }}" @selected(($filters['type'] ?? null) === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
        </div>
        <label class="drd-check"><input type="checkbox" name="overdue" value="1" @checked($filters['overdue'] ?? false)> Overdue only</label>
        <button type="submit" class="drd-button">Filter</button>
    </form>

    @if ($requests->isEmpty())
        <p class="drd-muted">No requests match.</p>
    @else
        <table class="drd-table">
            <thead>
                <tr>
                    <th scope="col">Type</th>
                    <th scope="col">Person</th>
                    <th scope="col">Status</th>
                    <th scope="col">Due</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($requests as $request)
                    <tr>
                        <td data-label="Type"><a href="{{ route('data-rights-desk.requests.show', $request) }}">{{ $request->type->label() }}</a> <span class="drd-muted drd-small">{{ $request->type->reference() }}</span></td>
                        <td data-label="Person">{{ $request->subject ? \Vipertecpro\DataRightsDesk\Support\Subjects::label($request->subject) : 'Erased or unknown' }}</td>
                        <td data-label="Status"><span @class(['drd-badge', 'drd-badge--ok' => $request->status === \Vipertecpro\DataRightsDesk\Enums\RequestStatus::Completed])>{{ $request->status->label() }}</span></td>
                        <td data-label="Due">
                            @if ($request->isClosed())
                                <span class="drd-muted">Closed {{ \Vipertecpro\DataRightsDesk\Support\Dates::day($request->closed_at) }}</span>
                            @else
                                <span @class(['drd-danger' => $request->isOverdue()])>{{ \Vipertecpro\DataRightsDesk\Support\Dates::due($request->due_at) }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="drd-pagination">
            @if ($requests->previousPageUrl())<a class="drd-button" href="{{ $requests->previousPageUrl() }}">Previous</a>@endif
            @if ($requests->nextPageUrl())<a class="drd-button" href="{{ $requests->nextPageUrl() }}">Next</a>@endif
        </div>
    @endif
@endsection
