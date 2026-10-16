@extends('data-rights::layouts.desk')

@section('title', $processor->exists ? 'Edit processor' : 'Add processor')

@section('content')
    <p><a href="{{ route('data-rights-desk.processors.index') }}">Back to processors</a></p>
    <h1>{{ $processor->exists ? 'Edit '.$processor->name : 'Add processor' }}</h1>

    <form method="POST" action="{{ $processor->exists ? route('data-rights-desk.processors.update', $processor) : route('data-rights-desk.processors.store') }}" class="drd-card">
        @csrf
        @if ($processor->exists)
            @method('PUT')
        @endif

        <div class="drd-field">
            <label for="drd-name">Name</label>
            <input id="drd-name" type="text" name="name" required maxlength="255" value="{{ old('name', $processor->name) }}">
        </div>
        <div class="drd-field">
            <label for="drd-kind">Kind</label>
            <select id="drd-kind" name="kind">
                @foreach (\Vipertecpro\DataRightsDesk\Enums\ProcessorKind::cases() as $kind)
                    <option value="{{ $kind->value }}" @selected(old('kind', $processor->kind?->value) === $kind->value)>{{ $kind->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="drd-field">
            <label for="drd-description">What they do with the data</label>
            <textarea id="drd-description" name="description" maxlength="5000">{{ old('description', $processor->description) }}</textarea>
        </div>
        <fieldset class="drd-field">
            <legend class="drd-label">Purposes</legend>
            @foreach ($purposes as $key => $purpose)
                <label class="drd-check"><input type="checkbox" name="purposes[]" value="{{ $key }}" @checked(in_array($key, old('purposes', $processor->purposes ?? []), true))> {{ $purpose->title() }}</label>
            @endforeach
        </fieldset>
        <div class="drd-field">
            <label for="drd-data">Personal data shared, one item per line</label>
            <textarea id="drd-data" name="data_shared" maxlength="5000">{{ old('data_shared', implode("\n", $processor->data_shared ?? [])) }}</textarea>
        </div>
        <div class="drd-grid drd-grid--2">
            <div class="drd-field">
                <label for="drd-contract">Contract reference</label>
                <input id="drd-contract" type="text" name="contract_reference" maxlength="255" value="{{ old('contract_reference', $processor->contract_reference) }}">
            </div>
            <div class="drd-field">
                <label for="drd-contract-date">Contract date</label>
                <input id="drd-contract-date" type="date" name="contract_date" value="{{ old('contract_date', $processor->contract_date?->format('Y-m-d')) }}">
            </div>
        </div>
        <div class="drd-field">
            <label for="drd-country">Country</label>
            <input id="drd-country" type="text" name="country" maxlength="64" value="{{ old('country', $processor->country) }}">
        </div>
        <input type="hidden" name="security_clause" value="0">
        <label class="drd-check"><input type="checkbox" name="security_clause" value="1" @checked(old('security_clause', $processor->security_clause))> The contract has a clause on reasonable security safeguards (Rule 6(1)(f))</label>
        <input type="hidden" name="hidden_from_access_summary" value="0">
        <label class="drd-check"><input type="checkbox" name="hidden_from_access_summary" value="1" @checked(old('hidden_from_access_summary', $processor->hidden_from_access_summary))> Leave out of access summaries (s.11(2): sharing with authorities for preventing or investigating offences)</label>
        <input type="hidden" name="active" value="0">
        <label class="drd-check"><input type="checkbox" name="active" value="1" @checked(old('active', $processor->exists ? $processor->active : true))> Active</label>

        <button type="submit" class="drd-button drd-button--primary">{{ $processor->exists ? 'Save processor' : 'Add processor' }}</button>
    </form>
@endsection
