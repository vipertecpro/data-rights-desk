<?php

namespace Vipertecpro\DataRightsDesk\Models;

use Closure;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Vipertecpro\DataRightsDesk\Enums\ConsentAction;
use Vipertecpro\DataRightsDesk\Enums\ConsentChannel;
use Vipertecpro\DataRightsDesk\Enums\GivenBy;
use Vipertecpro\DataRightsDesk\Exceptions\ConsentRecordImmutable;
use Vipertecpro\DataRightsDesk\Models\Concerns\BelongsToSubject;

/**
 * One row of the append-only consent ledger (s.6(10)): a consent given or
 * withdrawn. Rows are never changed. Only the retention keeper deletes them,
 * after the person's records have passed the keep period (Rule 8(3)).
 *
 * @property string $id
 * @property string $subject_type
 * @property int|string $subject_id
 * @property string $purpose
 * @property ConsentAction $action
 * @property string|null $notice_id
 * @property string|null $notice_hash
 * @property string|null $locale
 * @property ConsentChannel $channel
 * @property GivenBy $given_by
 * @property string|null $guardian_consent_id
 * @property string|null $consent_manager
 * @property string|null $external_reference
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $occurred_at
 * @property Carbon|null $created_at
 */
class ConsentRecord extends Model
{
    use BelongsToSubject;
    use HasUlids;

    public const UPDATED_AT = null;

    protected $table = 'data_rights_consents';

    /** Microseconds keep the order of two clicks in the same second. */
    protected $dateFormat = 'Y-m-d H:i:s.u';

    private static bool $retentionDeletes = false;

    /** @var list<string> */
    protected $fillable = [
        'subject_type',
        'subject_id',
        'purpose',
        'action',
        'notice_id',
        'notice_hash',
        'locale',
        'channel',
        'given_by',
        'guardian_consent_id',
        'consent_manager',
        'external_reference',
        'ip_address',
        'user_agent',
        'occurred_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'action' => ConsentAction::class,
            'channel' => ConsentChannel::class,
            'given_by' => GivenBy::class,
            'occurred_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (ConsentRecord $record): void {
            throw ConsentRecordImmutable::update($record->getKey());
        });

        static::deleting(function (ConsentRecord $record): void {
            if (! self::$retentionDeletes) {
                throw ConsentRecordImmutable::delete($record->getKey());
            }
        });
    }

    /**
     * Runs the callback with deletes allowed. Used only by the retention keeper.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function withRetentionDeletes(Closure $callback): mixed
    {
        self::$retentionDeletes = true;

        try {
            return $callback();
        } finally {
            self::$retentionDeletes = false;
        }
    }

    /** @return BelongsTo<Notice, $this> */
    public function notice(): BelongsTo
    {
        return $this->belongsTo(Notice::class, 'notice_id');
    }

    public function isGiven(): bool
    {
        return $this->action === ConsentAction::Given;
    }
}
