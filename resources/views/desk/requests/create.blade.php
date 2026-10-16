@extends('data-rights::layouts.desk')

@section('title', 'Record a request')

@section('content')
    <p><a href="{{ route('data-rights-desk.requests.index') }}">Back to requests</a></p>
    <h1>Record a request</h1>
    <p class="drd-lead">For a request that came by email, phone or letter. The clock starts on the day it was received.</p>

    <form method="POST" action="{{ route('data-rights-desk.requests.store') }}" class="drd-card">
        @csrf
        <div class="drd-field">
            <label for="drd-email">Person's email</label>
            <input id="drd-email" type="email" name="email" required maxlength="255" value="{{ old('email') }}" autocomplete="off">
        </div>
        <div class="drd-field">
            <label for="drd-type">Type</label>
            <select id="drd-type" name="type" required>
                @foreach ($types as $type)
                    <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }} ({{ $type->reference() }})</option>
                @endforeach
            </select>
        </div>
        <div class="drd-field">
            <label for="drd-received">Received on</label>
            <input id="drd-received" type="date" name="received_at" value="{{ old('received_at', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}">
        </div>
        <div class="drd-field">
            <label for="drd-details">What the person asked</label>
            <textarea id="drd-details" name="details" maxlength="5000">{{ old('details') }}</textarea>
            <span class="drd-help">Stored encrypted.</span>
        </div>
        <button type="submit" class="drd-button drd-button--primary">Record request</button>
    </form>
@endsection
