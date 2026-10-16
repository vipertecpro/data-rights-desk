<?php

namespace Vipertecpro\DataRightsDesk\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One run of the retention keeper. The summary holds counts only.
 *
 * @property string $id
 * @property Carbon $started_at
 * @property Carbon|null $finished_at
 * @property bool $pretend
 * @property array<string, mixed> $summary
 */
class RetentionRun extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $table = 'data_rights_retention_runs';

    /** @var list<string> */
    protected $fillable = [
        'started_at',
        'finished_at',
        'pretend',
        'summary',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'pretend' => 'boolean',
            'summary' => 'array',
        ];
    }
}
