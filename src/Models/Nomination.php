<?php

namespace Vipertecpro\DataRightsDesk\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Vipertecpro\DataRightsDesk\Models\Concerns\BelongsToSubject;

/**
 * s.14, Rule 14(4): a person nominated to exercise the rights after death or incapacity.
 *
 * @property string $id
 * @property string $nominee_name
 * @property string $nominee_contact
 * @property string|null $relationship
 * @property Carbon|null $created_at
 * @property Carbon|null $revoked_at
 */
class Nomination extends Model
{
    use BelongsToSubject;
    use HasUlids;

    public const UPDATED_AT = null;

    protected $table = 'data_rights_nominations';

    /** @var list<string> */
    protected $fillable = [
        'subject_type',
        'subject_id',
        'nominee_name',
        'nominee_contact',
        'relationship',
        'revoked_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'nominee_name' => 'encrypted',
            'nominee_contact' => 'encrypted',
            'relationship' => 'encrypted',
            'revoked_at' => 'datetime',
        ];
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
