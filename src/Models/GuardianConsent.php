<?php

namespace Vipertecpro\DataRightsDesk\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Vipertecpro\DataRightsDesk\Enums\GuardianKind;
use Vipertecpro\DataRightsDesk\Enums\GuardianMethod;
use Vipertecpro\DataRightsDesk\Models\Concerns\BelongsToSubject;

/**
 * s.9(1), Rules 10 and 11: verifiable consent of a parent or lawful guardian.
 *
 * @property string $id
 * @property GuardianKind $kind
 * @property GuardianMethod $method
 * @property string|null $guardian_type
 * @property int|string|null $guardian_id
 * @property string|null $guardian_name
 * @property string|null $verification_reference
 * @property Carbon $verified_at
 * @property string $verified_by
 * @property Carbon|null $revoked_at
 */
class GuardianConsent extends Model
{
    use BelongsToSubject;
    use HasUlids;

    protected $table = 'data_rights_guardian_consents';

    /** @var list<string> */
    protected $fillable = [
        'subject_type',
        'subject_id',
        'kind',
        'method',
        'guardian_type',
        'guardian_id',
        'guardian_name',
        'verification_reference',
        'verified_at',
        'verified_by',
        'revoked_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'kind' => GuardianKind::class,
            'method' => GuardianMethod::class,
            'guardian_name' => 'encrypted',
            'verification_reference' => 'encrypted',
            'verified_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function guardian(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }
}
