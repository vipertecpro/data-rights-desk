<?php

namespace Vipertecpro\DataRightsDesk\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Vipertecpro\DataRightsDesk\Enums\RequestChannel;
use Vipertecpro\DataRightsDesk\Enums\RequestStatus;
use Vipertecpro\DataRightsDesk\Enums\RequestType;
use Vipertecpro\DataRightsDesk\Models\Concerns\BelongsToSubject;
use Vipertecpro\DataRightsDesk\Support\Limits;

/**
 * A request from a Data Principal to exercise a right (s.11 to s.14).
 *
 * @property string $id
 * @property string|null $subject_type
 * @property int|string|null $subject_id
 * @property RequestType $type
 * @property RequestStatus $status
 * @property string|null $details
 * @property string|null $response
 * @property array<string, mixed>|null $result
 * @property RequestChannel $channel
 * @property string $locale
 * @property CarbonInterface $received_at
 * @property CarbonInterface $due_at
 * @property CarbonInterface|null $identity_verified_at
 * @property string|null $identity_method
 * @property CarbonInterface|null $closed_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
class DataRightsRequest extends Model
{
    use BelongsToSubject;
    use HasUlids;

    protected $table = 'data_rights_requests';

    /** @var list<string> */
    protected $fillable = [
        'subject_type',
        'subject_id',
        'type',
        'status',
        'details',
        'response',
        'result',
        'channel',
        'locale',
        'received_at',
        'due_at',
        'identity_verified_at',
        'identity_method',
        'closed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => RequestType::class,
            'status' => RequestStatus::class,
            'channel' => RequestChannel::class,
            'details' => 'encrypted',
            'response' => 'encrypted',
            'result' => 'array',
            'received_at' => 'datetime',
            'due_at' => 'datetime',
            'identity_verified_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /** @return HasMany<RequestEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(RequestEvent::class, 'request_id')->orderBy('created_at')->orderBy('id');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', array_map(fn (RequestStatus $s) => $s->value, RequestStatus::open()));
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->open()->where('due_at', '<', now());
    }

    public function isClosed(): bool
    {
        return $this->status->isClosed();
    }

    public function isOverdue(): bool
    {
        return ! $this->isClosed() && $this->due_at->isPast();
    }

    public function identityVerified(): bool
    {
        return $this->identity_verified_at !== null;
    }

    /** Whole days until the due date; negative when overdue. */
    public function daysLeft(): int
    {
        return (int) floor(now()->diffInDays($this->due_at, false));
    }

    /** True while the access summary can be downloaded (completed access requests only). */
    public function downloadable(): bool
    {
        if ($this->type !== RequestType::Access || $this->status !== RequestStatus::Completed || $this->closed_at === null) {
            return false;
        }

        return $this->closed_at->copy()->addDays(Limits::downloadDays())->isFuture();
    }
}
