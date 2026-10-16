@php($value = fn (string $field) => old($field, isset($breach) ? $breach->{$field} : null))
@php($dateValue = fn (string $field) => old($field, isset($breach) && $breach->{$field} ? $breach->{$field}->format('Y-m-d\TH:i') : null))
<div class="drd-field">
    <label for="drd-title">Short title</label>
    <input id="drd-title" type="text" name="title" maxlength="255" value="{{ $value('title') }}">
</div>
<div class="drd-field">
    <label for="drd-nature">Nature of the breach (Rule 7(1)(a), 7(2)(a))</label>
    <textarea id="drd-nature" name="nature" maxlength="20000" @if (! isset($breach)) required @endif>{{ $value('nature') }}</textarea>
</div>
<div class="drd-grid drd-grid--2">
    <div class="drd-field">
        <label for="drd-aware">Became aware at</label>
        <input id="drd-aware" type="datetime-local" name="became_aware_at" value="{{ $dateValue('became_aware_at') ?? now()->format('Y-m-d\TH:i') }}">
        <span class="drd-help">The 72-hour clock starts here.</span>
    </div>
    <div class="drd-field">
        <label for="drd-occurred">Occurred at (if known)</label>
        <input id="drd-occurred" type="datetime-local" name="occurred_at" value="{{ $dateValue('occurred_at') }}">
    </div>
</div>
@foreach ([
    'extent' => 'Extent: how much data and how many people',
    'location' => 'Location of occurrence',
    'likely_impact' => 'Likely impact',
    'consequences_for_people' => 'Consequences for the people affected (Rule 7(1)(b))',
    'mitigation' => 'Measures to mitigate risk (Rule 7(1)(c), 7(2)(b)(iii))',
    'safety_steps' => 'Safety steps people can take (Rule 7(1)(d))',
    'facts' => 'Broad facts: events, circumstances and reasons (Rule 7(2)(b)(ii))',
    'findings' => 'Findings about the person who caused it (Rule 7(2)(b)(iv))',
    'remedial' => 'Remedial measures to prevent it again (Rule 7(2)(b)(v))',
] as $field => $label)
    <div class="drd-field">
        <label for="drd-{{ $field }}">{{ $label }}</label>
        <textarea id="drd-{{ $field }}" name="{{ $field }}" maxlength="20000">{{ $value($field) }}</textarea>
    </div>
@endforeach
@isset($breach)
    <div class="drd-field">
        <label for="drd-extension">Longer period allowed by the Board, until</label>
        <input id="drd-extension" type="datetime-local" name="board_extension_until" value="{{ $dateValue('board_extension_until') }}">
        <span class="drd-help">Only when the Board allowed more time on a written request.</span>
    </div>
@endisset
<p class="drd-help">Every field is stored encrypted, except the title.</p>
