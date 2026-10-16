@extends('data-rights::layouts.desk')

@section('title', 'Children')

@section('content')
    <h1>Children and guardians</h1>
    <p class="drd-lead">s.9: verifiable consent of a parent or lawful guardian, and no tracking of children. Only the age status is stored, never a birth date.</p>

    @unless ($enabled)
        <p class="drd-alert drd-alert--warn">Checks for children are off (children.enabled is false).</p>
    @endunless

    @if ($enabled && ! $verifierBound)
        <p class="drd-alert drd-alert--warn">No ParentalConsentVerifier is bound, so a parent cannot confirm consent yet (Rule 10).</p>
    @endif

    <div class="drd-grid drd-grid--3">
        <div class="drd-stat drd-stat--key">
            <div class="drd-stat__label">Declared under 18</div>
            <div class="drd-stat__value">{{ $children }}</div>
        </div>
        <div class="drd-stat">
            <div class="drd-stat__label">Declared 18 or older</div>
            <div class="drd-stat__value">{{ $adults }}</div>
        </div>
        <div class="drd-stat">
            <div class="drd-stat__label">With a lawful guardian</div>
            <div class="drd-stat__value">{{ $withGuardian }}</div>
        </div>
    </div>
    <p class="drd-small drd-muted">People who never answered count as {{ $unknownIsChild ? 'children' : 'adults' }} (children.unknown_age_is_child).</p>

    <h2>Guardian consents</h2>
    @if ($consents->isEmpty())
        <p class="drd-muted">None recorded.</p>
    @else
        <table class="drd-table">
            <thead>
                <tr><th scope="col">Person</th><th scope="col">Kind and method</th><th scope="col">Verified</th><th scope="col">State</th></tr>
            </thead>
            <tbody>
                @foreach ($consents as $consent)
                    <tr>
                        <td data-label="Person">{{ $consent->subject ? \Vipertecpro\DataRightsDesk\Support\Subjects::label($consent->subject) : 'Erased' }}</td>
                        <td data-label="Kind and method">{{ str_replace('_', ' ', $consent->kind->value) }}: {{ str_replace('_', ' ', $consent->method->value) }}</td>
                        <td data-label="Verified">{{ \Vipertecpro\DataRightsDesk\Support\Dates::time($consent->verified_at) }}</td>
                        <td data-label="State">{{ $consent->revoked_at ? 'Revoked' : 'Active' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Fourth Schedule keys</h2>
    <p class="drd-small">A purpose with one of these keys skips s.9(1) and s.9(3). You declare that the condition is met; the package cannot check it.</p>
    <dl class="drd-dl">
        @foreach (\Vipertecpro\DataRightsDesk\Support\FourthSchedule::ENTRIES as $key => $entry)
            <dt>{{ $key }}</dt>
            <dd>{{ $entry['applies_to'] }} <span class="drd-muted">{{ $entry['condition'] }}</span></dd>
        @endforeach
    </dl>

    <p><a class="drd-button drd-button--primary" href="{{ route('data-rights-desk.check') }}">Run the check</a></p>
@endsection
