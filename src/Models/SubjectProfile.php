<?php

namespace Vipertecpro\DataRightsDesk\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Vipertecpro\DataRightsDesk\Enums\AgeStatus;
use Vipertecpro\DataRightsDesk\Models\Concerns\BelongsToSubject;

/**
 * One row per Data Principal the package knows about.
 *
 * @property string $id
 * @property string $subject_type
 * @property int|string $subject_id
 * @property AgeStatus|null $age_status
 * @property Carbon|null $age_declared_at
 * @property bool $has_lawful_guardian
 * @property Carbon|null $last_seen_at
 * @property Carbon|null $erasure_warned_at
 * @property Carbon|null $erasure_due_at
 * @property Carbon|null $erased_at
 */
class SubjectProfile extends Model
{
    use BelongsToSubject;
    use HasUlids;

    protected $table = 'data_rights_subjects';

    /** @var list<string> */
    protected $fillable = [
        'subject_type',
        'subject_id',
        'age_status',
        'age_declared_at',
        'has_lawful_guardian',
        'last_seen_at',
        'erasure_warned_at',
        'erasure_due_at',
        'erased_at',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'has_lawful_guardian' => false,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'age_status' => AgeStatus::class,
            'age_declared_at' => 'datetime',
            'has_lawful_guardian' => 'boolean',
            'last_seen_at' => 'datetime',
            'erasure_warned_at' => 'datetime',
            'erasure_due_at' => 'datetime',
            'erased_at' => 'datetime',
        ];
    }

    /** The profile for a subject, created when missing. */
    public static function for(Model $subject): self
    {
        return static::query()->firstOrCreate([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
        ]);
    }

    /** The profile for a subject, or null when the package has none yet. */
    public static function lookup(Model $subject): ?self
    {
        return static::query()->forSubject($subject)->first();
    }
}
