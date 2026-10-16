<?php

namespace Vipertecpro\DataRightsDesk\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Vipertecpro\DataRightsDesk\Models\Concerns\BelongsToSubject;

/**
 * Rule 7(1): a person affected by a breach, and when she was told.
 *
 * @property string $id
 * @property string $breach_id
 * @property string $subject_type
 * @property int|string $subject_id
 * @property Carbon|null $notified_at
 * @property string|null $channel
 */
class BreachSubject extends Model
{
    use BelongsToSubject;
    use HasUlids;

    public $timestamps = false;

    protected $table = 'data_rights_breach_subjects';

    /** @var list<string> */
    protected $fillable = [
        'breach_id',
        'subject_type',
        'subject_id',
        'notified_at',
        'channel',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'notified_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Breach, $this> */
    public function breach(): BelongsTo
    {
        return $this->belongsTo(Breach::class, 'breach_id');
    }
}
