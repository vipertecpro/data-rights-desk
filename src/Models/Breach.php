<?php

namespace Vipertecpro\DataRightsDesk\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * s.8(6), Rule 7: a personal data breach and the drafts the business sends.
 *
 * @property string $id
 * @property string $title
 * @property string $nature
 * @property string|null $extent
 * @property string|null $location
 * @property string|null $likely_impact
 * @property string|null $facts
 * @property string|null $mitigation
 * @property string|null $findings
 * @property string|null $remedial
 * @property string|null $consequences_for_people
 * @property string|null $safety_steps
 * @property Carbon|null $occurred_at
 * @property Carbon $became_aware_at
 * @property Carbon|null $board_alerted_at
 * @property Carbon $board_report_due_at
 * @property Carbon|null $board_extension_until
 * @property Carbon|null $board_reported_at
 * @property Carbon|null $closed_at
 * @property Carbon|null $created_at
 */
class Breach extends Model
{
    use HasUlids;

    /** Rule 7(2)(b): the detailed report is due within seventy-two hours of becoming aware. */
    public const REPORT_HOURS = 72;

    /** @var list<string> */
    public const TEXT_FIELDS = [
        'nature', 'extent', 'location', 'likely_impact', 'facts', 'mitigation',
        'findings', 'remedial', 'consequences_for_people', 'safety_steps',
    ];

    protected $table = 'data_rights_breaches';

    /** @var list<string> */
    protected $fillable = [
        'title',
        'nature',
        'extent',
        'location',
        'likely_impact',
        'facts',
        'mitigation',
        'findings',
        'remedial',
        'consequences_for_people',
        'safety_steps',
        'occurred_at',
        'became_aware_at',
        'board_alerted_at',
        'board_report_due_at',
        'board_extension_until',
        'board_reported_at',
        'closed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'nature' => 'encrypted',
            'extent' => 'encrypted',
            'location' => 'encrypted',
            'likely_impact' => 'encrypted',
            'facts' => 'encrypted',
            'mitigation' => 'encrypted',
            'findings' => 'encrypted',
            'remedial' => 'encrypted',
            'consequences_for_people' => 'encrypted',
            'safety_steps' => 'encrypted',
            'occurred_at' => 'datetime',
            'became_aware_at' => 'datetime',
            'board_alerted_at' => 'datetime',
            'board_report_due_at' => 'datetime',
            'board_extension_until' => 'datetime',
            'board_reported_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /** @return HasMany<BreachSubject, $this> */
    public function affected(): HasMany
    {
        return $this->hasMany(BreachSubject::class, 'breach_id');
    }

    /** The report deadline, or the longer period the Board allowed (Rule 7(2)(b)). */
    public function reportDueAt(): Carbon
    {
        if ($this->board_extension_until !== null && $this->board_extension_until->greaterThan($this->board_report_due_at)) {
            return $this->board_extension_until;
        }

        return $this->board_report_due_at;
    }

    public function isReportOverdue(): bool
    {
        return $this->board_reported_at === null && $this->reportDueAt()->isPast();
    }

    /** Whole hours left to the report deadline; negative when overdue. */
    public function hoursLeft(): int
    {
        return (int) floor(now()->diffInHours($this->reportDueAt(), false));
    }
}
