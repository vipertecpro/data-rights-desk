@extends('data-rights::layouts.public')

@section('title', __('data-rights::public.requests.title'))

@section('content')
    <h1>{{ __('data-rights::public.requests.title') }}</h1>
    <p class="drd-lead">{{ __('data-rights::public.requests.intro', ['days' => $days]) }}</p>

    <section class="drd-card" aria-labelledby="drd-new">
        <h2 id="drd-new">{{ __('data-rights::public.requests.new_heading') }}</h2>
        <form method="POST" action="{{ route('data-rights.requests.store') }}">
            @csrf
            <input type="hidden" name="lang" value="{{ $locale }}">
            <div class="drd-field">
                <label for="drd-type">{{ __('data-rights::public.requests.type') }}</label>
                <select id="drd-type" name="type" required>
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ __('data-rights::public.types.'.$type->value) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="drd-field">
                <label for="drd-details">{{ __('data-rights::public.requests.details') }}</label>
                <textarea id="drd-details" name="details" maxlength="5000" aria-describedby="drd-details-help">{{ old('details') }}</textarea>
                <span id="drd-details-help" class="drd-help">{{ __('data-rights::public.requests.details_help') }}</span>
            </div>
            <button type="submit" class="drd-button drd-button--primary">{{ __('data-rights::public.requests.submit') }}</button>
        </form>
    </section>

    <h2>{{ __('data-rights::public.requests.list_heading') }}</h2>
    @if ($requests->isEmpty())
        <p class="drd-muted">{{ __('data-rights::public.requests.none') }}</p>
    @else
        <table class="drd-table">
            <thead>
                <tr>
                    <th scope="col">{{ __('data-rights::public.requests.type') }}</th>
                    <th scope="col">{{ __('data-rights::public.requests.status') }}</th>
                    <th scope="col"><span class="drd-sr">{{ __('data-rights::public.requests.open') }}</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($requests as $request)
                    <tr>
                        <td data-label="{{ __('data-rights::public.requests.type') }}">
                            {{ __('data-rights::public.types.'.$request->type->value) }}
                            <span class="drd-help">{{ __('data-rights::public.requests.received', ['date' => \Vipertecpro\DataRightsDesk\Support\Dates::day($request->received_at, $locale)]) }}</span>
                        </td>
                        <td data-label="{{ __('data-rights::public.requests.status') }}">
                            <span class="drd-badge">{{ __('data-rights::public.statuses.'.$request->status->value) }}</span>
                            @unless ($request->isClosed())
                                <span class="drd-help">{{ __('data-rights::public.requests.due', ['date' => \Vipertecpro\DataRightsDesk\Support\Dates::day($request->due_at, $locale)]) }}</span>
                            @endunless
                        </td>
                        <td><a href="{{ route('data-rights.requests.show', ['request' => $request, ...$lang]) }}">{{ __('data-rights::public.requests.open') }}</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2 id="drd-nominations">{{ __('data-rights::public.nominations.heading') }}</h2>
    <p>{{ __('data-rights::public.nominations.intro') }}</p>

    @if ($nominations->isEmpty())
        <p class="drd-muted">{{ __('data-rights::public.nominations.none') }}</p>
    @else
        <ul class="drd-list">
            @foreach ($nominations as $nomination)
                <li>
                    {{ $nomination->nominee_name }} ({{ $nomination->nominee_contact }}){{ $nomination->relationship ? ', '.$nomination->relationship : '' }}
                    <form method="POST" action="{{ route('data-rights.nominations.destroy', $nomination) }}" class="drd-inline">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="lang" value="{{ $locale }}">
                        <button type="submit" class="drd-button">{{ __('data-rights::public.nominations.revoke') }}</button>
                    </form>
                </li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('data-rights.nominations.store') }}" class="drd-card">
        @csrf
        <input type="hidden" name="lang" value="{{ $locale }}">
        <div class="drd-field">
            <label for="drd-nominee-name">{{ __('data-rights::public.nominations.name') }}</label>
            <input id="drd-nominee-name" type="text" name="nominee_name" maxlength="200" required value="{{ old('nominee_name') }}">
        </div>
        <div class="drd-field">
            <label for="drd-nominee-contact">{{ __('data-rights::public.nominations.contact') }}</label>
            <input id="drd-nominee-contact" type="text" name="nominee_contact" maxlength="200" required value="{{ old('nominee_contact') }}">
        </div>
        <div class="drd-field">
            <label for="drd-relationship">{{ __('data-rights::public.nominations.relationship') }}</label>
            <input id="drd-relationship" type="text" name="relationship" maxlength="100" value="{{ old('relationship') }}">
        </div>
        <button type="submit" class="drd-button">{{ __('data-rights::public.nominations.submit') }}</button>
    </form>
@endsection
