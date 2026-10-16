@extends('data-rights::layouts.desk')

@section('title', $request->type->label().' request')

@section('content')
    @php($D = \Vipertecpro\DataRightsDesk\Support\Dates::class)
    @php($open = ! $request->isClosed())
    @php($isErasure = $request->type === \Vipertecpro\DataRightsDesk\Enums\RequestType::Erasure)
    <p><a href="{{ route('data-rights-desk.requests.index') }}">Back to requests</a></p>

    <div class="drd-pagehead">
        <div>
            <h1>{{ $request->type->label() }} request <span class="drd-muted drd-small">{{ $request->type->reference() }}</span></h1>
            <p><span class="drd-badge">{{ $request->status->label() }}</span>
                @if ($open)
                    <span @class(['drd-danger' => $request->isOverdue()])>{{ $D::due($request->due_at) }}</span>
                @endif
            </p>
        </div>
    </div>

    <dl class="drd-dl">
        <dt>Person</dt>
        <dd>{{ $subject ? \Vipertecpro\DataRightsDesk\Support\Subjects::label($subject) : 'Erased or unknown' }}</dd>
        <dt>Reference</dt>
        <dd>{{ $request->getKey() }}</dd>
        <dt>Received</dt>
        <dd>{{ $D::time($request->received_at) }} via {{ $request->channel->value }}</dd>
        <dt>Identity</dt>
        <dd>
            @if ($request->identityVerified())
                Checked ({{ $request->identity_method }}) on {{ $D::time($request->identity_verified_at) }}
            @else
                <span class="drd-danger">Not checked yet</span>
            @endif
        </dd>
        @if ($request->closed_at)
            <dt>Closed</dt>
            <dd>{{ $D::time($request->closed_at) }}</dd>
        @endif
    </dl>

    @if (filled($request->details))
        <h2>What the person asked</h2>
        <div class="drd-card">{!! nl2br(e($request->details)) !!}</div>
    @endif

    @if (filled($request->response))
        <h2>Reply</h2>
        <div class="drd-card">{!! nl2br(e($request->response)) !!}</div>
    @endif

    @if ($request->result)
        <h2>Erasure report</h2>
        @if ($request->result['held'] ?? false)
            <p class="drd-alert drd-alert--warn">A legal hold on this person stopped the erasure.</p>
        @endif
        <pre class="drd-pre">{{ json_encode($request->result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
    @endif

    @if ($request->type === \Vipertecpro\DataRightsDesk\Enums\RequestType::Access && $subject)
        <p><a href="{{ route('data-rights-desk.requests.summary', $request) }}">Preview the access summary</a></p>
    @endif

    @if ($open)
        @unless ($request->identityVerified())
            <form method="POST" action="{{ route('data-rights-desk.requests.verify', $request) }}" class="drd-card">
                @csrf
                <h2>Check identity</h2>
                <p class="drd-small drd-muted">Rule 14(1)(b): confirm the person is who they say, then record how you checked.</p>
                <div class="drd-field">
                    <label for="drd-method">How you checked</label>
                    <input id="drd-method" type="text" name="method" required maxlength="120" placeholder="Reply from the registered email address">
                </div>
                <button type="submit" @class(['drd-button', 'drd-button--primary' => $request->type !== \Vipertecpro\DataRightsDesk\Enums\RequestType::Grievance])>Record identity check</button>
            </form>
        @endunless

        @if ($isErasure)
            <form method="POST" action="{{ route('data-rights-desk.requests.erase', $request) }}" class="drd-card">
                @csrf
                <h2>Erase personal data</h2>
                <p class="drd-small">Runs every Eraser, deletes nominations, marks the person erased and sends the reply to the address on file. This cannot be undone.</p>
                <label class="drd-check"><input type="checkbox" name="confirm" value="1" required> I checked the person's identity and want to erase their data.</label>
                <button type="submit" @class(['drd-button', 'drd-button--danger', 'drd-button--primary' => $request->identityVerified()])>Erase personal data</button>
            </form>
        @else
            <form method="POST" action="{{ route('data-rights-desk.requests.complete', $request) }}" class="drd-card">
                @csrf
                <h2>Reply and complete</h2>
                <div class="drd-field">
                    <label for="drd-response">Reply to the person</label>
                    <textarea id="drd-response" name="response" required maxlength="10000"></textarea>
                    <span class="drd-help">Sent with the contact block. Stored encrypted.</span>
                </div>
                <button type="submit" @class(['drd-button', 'drd-button--primary' => $request->identityVerified() || $request->type === \Vipertecpro\DataRightsDesk\Enums\RequestType::Grievance])>Complete request</button>
            </form>
        @endif

        <div class="drd-grid drd-grid--2">
            <form method="POST" action="{{ route('data-rights-desk.requests.status', $request) }}" class="drd-card">
                @csrf
                <h2>Status</h2>
                <div class="drd-field">
                    <label for="drd-status">New status</label>
                    <select id="drd-status" name="status">
                        <option value="in_progress" @selected($request->status->value === 'received')>In progress</option>
                        <option value="received" @selected($request->status->value === 'in_progress')>Received</option>
                    </select>
                </div>
                <div class="drd-field">
                    <label for="drd-note">Note (staff only)</label>
                    <textarea id="drd-note" name="note" maxlength="2000"></textarea>
                </div>
                <button type="submit" class="drd-button">Change status</button>
            </form>

            <form method="POST" action="{{ route('data-rights-desk.requests.reject', $request) }}" class="drd-card">
                @csrf
                <h2>Reject</h2>
                <div class="drd-field">
                    <label for="drd-reject">Why it cannot be done</label>
                    <textarea id="drd-reject" name="response" required maxlength="10000"></textarea>
                </div>
                <button type="submit" class="drd-button drd-button--danger">Reject request</button>
            </form>
        </div>
    @endif

    <h2>History</h2>
    <table class="drd-table">
        <thead>
            <tr><th scope="col">When</th><th scope="col">Change</th><th scope="col">Note</th></tr>
        </thead>
        <tbody>
            @foreach ($request->events as $event)
                <tr>
                    <td data-label="When">{{ $D::time($event->created_at) }}</td>
                    <td data-label="Change">{{ $event->from_status ? $event->from_status->label().' to ' : '' }}{{ $event->to_status->label() }}</td>
                    <td data-label="Note">{{ $event->note }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
