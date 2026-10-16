<?php

namespace Vipertecpro\DataRightsDesk\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Vipertecpro\DataRightsDesk\Enums\RequestStatus;

/**
 * @property string $id
 * @property string $request_id
 * @property RequestStatus|null $from_status
 * @property RequestStatus $to_status
 * @property string|null $actor_type
 * @property int|string|null $actor_id
 * @property string|null $note
 * @property CarbonInterface|null $created_at
 */
class RequestEvent extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    protected $table = 'data_rights_request_events';

    /** @var list<string> */
    protected $fillable = [
        'request_id',
        'from_status',
        'to_status',
        'actor_type',
        'actor_id',
        'note',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'from_status' => RequestStatus::class,
            'to_status' => RequestStatus::class,
            'note' => 'encrypted',
        ];
    }

    /** @return BelongsTo<DataRightsRequest, $this> */
    public function request(): BelongsTo
    {
        return $this->belongsTo(DataRightsRequest::class, 'request_id');
    }

    /** @return MorphTo<Model, $this> */
    public function actor(): MorphTo
    {
        return $this->morphTo();
    }
}
