<?php

namespace Vipertecpro\DataRightsDesk\Breaches;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Vipertecpro\DataRightsDesk\Enums\BreachDraftKind;
use Vipertecpro\DataRightsDesk\Events\BreachRecorded;
use Vipertecpro\DataRightsDesk\Models\Breach;
use Vipertecpro\DataRightsDesk\Models\BreachSubject;
use Vipertecpro\DataRightsDesk\Notifications\BreachNotification;
use Vipertecpro\DataRightsDesk\Support\Contact;
use Vipertecpro\DataRightsDesk\Support\Dates;
use Vipertecpro\DataRightsDesk\Support\Languages;
use Vipertecpro\DataRightsDesk\Support\Subjects;

/**
 * s.8(6), Rule 7: records a personal data breach and drafts the intimations.
 * The package drafts; the business checks every detail and sends them.
 */
class BreachDesk
{
    /** @param  array<string, mixed>  $attributes */
    public function record(array $attributes): Breach
    {
        if (blank($attributes['nature'] ?? null)) {
            throw new InvalidArgumentException('Describe the nature of the breach.');
        }

        $awareAt = $this->date($attributes['became_aware_at'] ?? null) ?? now();

        $breach = new Breach;
        $breach->fill($this->only($attributes));
        $breach->forceFill([
            'title' => filled($attributes['title'] ?? null) ? (string) $attributes['title'] : 'Breach of '.Dates::day($awareAt),
            'became_aware_at' => $awareAt,
            'board_report_due_at' => $awareAt->copy()->addHours(Breach::REPORT_HOURS),
        ])->save();

        event(new BreachRecorded($breach));

        return $breach;
    }

    /** @param  array<string, mixed>  $attributes */
    public function update(Breach $breach, array $attributes): Breach
    {
        $breach->fill($this->only($attributes));

        if (filled($attributes['title'] ?? null)) {
            $breach->title = (string) $attributes['title'];
        }

        $awareAt = $this->date($attributes['became_aware_at'] ?? null);

        if ($awareAt !== null) {
            $breach->forceFill([
                'became_aware_at' => $awareAt,
                'board_report_due_at' => $awareAt->copy()->addHours(Breach::REPORT_HOURS),
            ]);
        }

        $breach->save();

        return $breach;
    }

    /**
     * @param  iterable<Model>  $subjects
     * @return int the number of people newly added
     */
    public function addAffected(Breach $breach, iterable $subjects): int
    {
        $added = 0;

        foreach ($subjects as $subject) {
            $row = BreachSubject::query()->firstOrCreate([
                'breach_id' => $breach->getKey(),
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
            ]);

            $added += $row->wasRecentlyCreated ? 1 : 0;
        }

        return $added;
    }

    /** Rule 7(2)(a): the first intimation to the Board, without delay. */
    public function boardAlertDraft(Breach $breach): string
    {
        $lines = [
            'Personal data breach: intimation to the Data Protection Board of India',
            'Rule 7(2)(a) of the Digital Personal Data Protection Rules, 2025',
            '',
            ...$this->fiduciaryLines(),
            '',
            'Description of the breach',
            ...$this->descriptionLines($breach),
            '',
            $this->draftFooter(),
        ];

        return implode("\n", $lines)."\n";
    }

    /** Rule 7(2)(b): the detailed report within 72 hours, items (i) to (vi). */
    public function boardReportDraft(Breach $breach): string
    {
        $affected = $breach->affected()->count();
        $told = $breach->affected()->whereNotNull('notified_at')->count();
        $first = $breach->affected()->whereNotNull('notified_at')->min('notified_at');
        $last = $breach->affected()->whereNotNull('notified_at')->max('notified_at');

        $intimations = "{$affected} people recorded as affected; {$told} told so far";

        if ($told > 0 && $first !== null && $last !== null) {
            $intimations .= ' (first on '.Dates::time(Carbon::parse((string) $first)).', last on '.Dates::time(Carbon::parse((string) $last)).')';
        }

        $due = 'Due by '.Dates::time($breach->reportDueAt());

        if ($breach->board_extension_until !== null) {
            $due .= ' (longer period allowed by the Board)';
        }

        $lines = [
            'Personal data breach: report to the Data Protection Board of India',
            'Rule 7(2)(b) of the Digital Personal Data Protection Rules, 2025',
            $due,
            '',
            ...$this->fiduciaryLines(),
            '',
            '(i) Updated and detailed description',
            ...$this->descriptionLines($breach),
            '',
            '(ii) Broad facts: events, circumstances and reasons leading to the breach',
            $this->value($breach->facts),
            '',
            '(iii) Measures implemented or proposed to mitigate risk',
            $this->value($breach->mitigation),
            '',
            '(iv) Findings regarding the person who caused the breach',
            $this->value($breach->findings),
            '',
            '(v) Remedial measures taken to prevent recurrence',
            $this->value($breach->remedial),
            '',
            '(vi) Intimations given to affected Data Principals',
            $intimations.'.',
            '',
            $this->draftFooter(),
        ];

        return implode("\n", $lines)."\n";
    }

    /**
     * Rule 7(1)(a)-(e): the message to each affected person, in her language.
     */
    public function personMessage(Breach $breach, ?string $locale = null): string
    {
        $locale = Languages::resolve($locale);
        $t = fn (string $key, array $replace = []): string => (string) __("data-rights::mail.breach.{$key}", $replace, $locale);
        $unknown = $t('not_known');

        $lines = [
            (string) __('data-rights::mail.greeting', [], $locale),
            '',
            $t('intro'),
            '',
            '(a) '.$t('what'),
            $breach->nature,
        ];

        if (filled($breach->extent)) {
            $lines[] = $t('extent', ['value' => $breach->extent]);
        }

        $lines[] = $breach->occurred_at !== null
            ? $t('timing', ['value' => Dates::day($breach->occurred_at, $locale)])
            : $t('timing_unknown');
        $lines[] = $t('aware', ['date' => Dates::day($breach->became_aware_at, $locale)]);

        array_push(
            $lines,
            '',
            '(b) '.$t('consequences'),
            filled($breach->consequences_for_people) ? (string) $breach->consequences_for_people : $unknown,
            '',
            '(c) '.$t('mitigation'),
            filled($breach->mitigation) ? (string) $breach->mitigation : $unknown,
            '',
            '(d) '.$t('safety'),
            filled($breach->safety_steps) ? (string) $breach->safety_steps : $unknown,
            '',
            '(e) '.$t('contact'),
            ...Contact::lines($locale),
        );

        return implode("\n", $lines)."\n";
    }

    public function draft(Breach $breach, BreachDraftKind $kind, ?string $locale = null): string
    {
        return match ($kind) {
            BreachDraftKind::Alert => $this->boardAlertDraft($breach),
            BreachDraftKind::Report => $this->boardReportDraft($breach),
            BreachDraftKind::Person => $this->personMessage($breach, $locale),
        };
    }

    public function markBoardAlerted(Breach $breach): Breach
    {
        if ($breach->board_alerted_at === null) {
            $breach->forceFill(['board_alerted_at' => now()])->save();
        }

        return $breach;
    }

    public function markBoardReported(Breach $breach): Breach
    {
        if ($breach->board_reported_at === null) {
            $breach->forceFill(['board_reported_at' => now()])->save();
        }

        return $breach;
    }

    /**
     * Rule 7(1): tells each affected person once. Returns how many were told now.
     */
    public function notifyAffected(Breach $breach): int
    {
        $sent = 0;
        $channels = implode(',', array_map('strval', (array) config('data-rights.notifications.channels', ['mail'])));

        $breach->affected()->whereNull('notified_at')->orderBy('id')->each(function (BreachSubject $row) use ($breach, $channels, &$sent): void {
            $subject = Subjects::find($row->subject_type, $row->subject_id);

            if ($subject === null) {
                return;
            }

            $locale = Languages::resolve(Subjects::locale($subject));

            if (Subjects::notify($subject, (new BreachNotification($breach))->locale($locale))) {
                $row->forceFill(['notified_at' => now(), 'channel' => $channels])->save();
                $sent++;
            }
        });

        return $sent;
    }

    /** @return list<string> */
    private function fiduciaryLines(): array
    {
        $details = Contact::details();

        return array_values(array_filter([
            'Data Fiduciary: '.Contact::businessName(),
            $details['website'] !== null ? 'Website: '.$details['website'] : null,
            'Contact: '.implode(', ', array_filter([$details['name'], $details['role'], $details['email'], $details['phone']])),
            $details['address'] !== null ? 'Address: '.$details['address'] : null,
        ]));
    }

    /** @return list<string> */
    private function descriptionLines(Breach $breach): array
    {
        return [
            'Nature: '.$breach->nature,
            'Extent: '.$this->value($breach->extent),
            'Timing: '.($breach->occurred_at !== null ? 'occurred '.Dates::time($breach->occurred_at) : 'time of occurrence not yet known')
                .'; became aware '.Dates::time($breach->became_aware_at),
            'Location: '.$this->value($breach->location),
            'Likely impact: '.$this->value($breach->likely_impact),
        ];
    }

    private function value(?string $text): string
    {
        return filled($text) ? (string) $text : 'Not known yet.';
    }

    private function draftFooter(): string
    {
        return 'Draft prepared with Data Rights Desk. Check every detail before sending it to the Board.';
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function only(array $attributes): array
    {
        $allowed = [...Breach::TEXT_FIELDS, 'occurred_at', 'board_extension_until'];
        $values = array_intersect_key($attributes, array_flip($allowed));

        foreach (['occurred_at', 'board_extension_until'] as $dateField) {
            if (array_key_exists($dateField, $values)) {
                $values[$dateField] = $this->date($values[$dateField]);
            }
        }

        foreach (Breach::TEXT_FIELDS as $field) {
            if (array_key_exists($field, $values) && blank($values[$field]) && $field !== 'nature') {
                $values[$field] = null;
            }
        }

        if (array_key_exists('nature', $values) && blank($values['nature'])) {
            unset($values['nature']);
        }

        return $values;
    }

    private function date(mixed $value): ?Carbon
    {
        if ($value instanceof CarbonInterface) {
            return Carbon::instance($value);
        }

        return is_string($value) && $value !== '' ? Carbon::parse($value) : null;
    }
}
