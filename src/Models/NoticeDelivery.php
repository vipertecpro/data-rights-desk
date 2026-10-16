<?php

namespace Vipertecpro\DataRightsDesk\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Vipertecpro\DataRightsDesk\Enums\DeliveryChannel;
use Vipertecpro\DataRightsDesk\Models\Concerns\BelongsToSubject;

/**
 * s.5(2): a notice sent to a person.
 *
 * @property string $id
 * @property string $notice_id
 * @property DeliveryChannel $channel
 * @property Carbon $delivered_at
 */
class NoticeDelivery extends Model
{
    use BelongsToSubject;
    use HasUlids;

    public $timestamps = false;

    protected $table = 'data_rights_notice_deliveries';

    /** @var list<string> */
    protected $fillable = [
        'notice_id',
        'subject_type',
        'subject_id',
        'channel',
        'delivered_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'channel' => DeliveryChannel::class,
            'delivered_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Notice, $this> */
    public function notice(): BelongsTo
    {
        return $this->belongsTo(Notice::class, 'notice_id');
    }
}
