<?php

namespace Vipertecpro\DataRightsDesk\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Vipertecpro\DataRightsDesk\Models\Concerns\BelongsToSubject;

/**
 * Rule 8(3), s.11(1)(a): a log line of processing. Short activity labels only,
 * never free text or personal data.
 *
 * @property string $id
 * @property string $purpose
 * @property string $activity
 * @property Carbon $occurred_at
 */
class ProcessingLog extends Model
{
    use BelongsToSubject;
    use HasUlids;

    public $timestamps = false;

    protected $table = 'data_rights_processing_logs';

    /** @var list<string> */
    protected $fillable = [
        'subject_type',
        'subject_id',
        'purpose',
        'activity',
        'occurred_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }
}
