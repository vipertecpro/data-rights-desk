<?php

namespace Vipertecpro\DataRightsDesk\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * A legal hold keeps a record from erasure while another law needs it kept.
 *
 * @property string $id
 * @property string $holdable_type
 * @property string $holdable_id
 * @property string $reason
 * @property string|null $law_reference
 * @property Carbon $placed_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $released_at
 */
class LegalHold extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $table = 'data_rights_legal_holds';

    /** @var list<string> */
    protected $fillable = [
        'holdable_type',
        'holdable_id',
        'reason',
        'law_reference',
        'placed_by_type',
        'placed_by_id',
        'placed_at',
        'expires_at',
        'released_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'reason' => 'encrypted',
            'placed_at' => 'datetime',
            'expires_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function holdable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return MorphTo<Model, $this> */
    public function placedBy(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('released_at')
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function isActive(): bool
    {
        return $this->released_at === null && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /** True when an active hold covers this record. */
    public static function covers(Model $record): bool
    {
        return static::query()->active()
            ->where('holdable_type', $record->getMorphClass())
            ->where('holdable_id', (string) $record->getKey())
            ->exists();
    }

    /**
     * Keys of held records of one morph class.
     *
     * @return list<string>
     */
    public static function heldKeys(string $morphClass): array
    {
        return static::query()->active()
            ->where('holdable_type', $morphClass)
            ->pluck('holdable_id')
            ->map(fn (mixed $id): string => (string) $id)
            ->values()
            ->all();
    }
}
