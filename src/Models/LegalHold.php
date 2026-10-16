<?php

namespace Vipertecpro\DataRightsDesk\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A legal hold keeps a record from erasure while another law needs it kept.
 *
 * @property string $id
 * @property string $holdable_type
 * @property string $holdable_id
 * @property string $reason
 * @property string|null $law_reference
 * @property CarbonInterface $placed_at
 * @property CarbonInterface|null $expires_at
 * @property CarbonInterface|null $released_at
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

    /** True when an active hold covers the record with this morph class and key. */
    public static function coversKey(string $morphClass, int|string $key): bool
    {
        return static::query()->active()
            ->where('holdable_type', $morphClass)
            ->where('holdable_id', (string) $key)
            ->exists();
    }

    /**
     * The keys, among the given ones, of records of one morph class that are held.
     * Callers pass one chunk at a time, so the list stays small.
     *
     * @param  array<int, int|string>  $keys
     * @return array<string, true>
     */
    public static function heldAmong(string $morphClass, array $keys): array
    {
        if ($keys === []) {
            return [];
        }

        $held = [];

        foreach (static::query()->active()
            ->where('holdable_type', $morphClass)
            ->whereIn('holdable_id', array_map(fn (int|string $key): string => (string) $key, $keys))
            ->pluck('holdable_id') as $id) {
            $held[(string) $id] = true;
        }

        return $held;
    }
}
