<?php

namespace Vipertecpro\DataRightsDesk\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Vipertecpro\DataRightsDesk\Enums\ProcessorKind;

/**
 * s.8(2), Rule 6(1)(f), s.11(1)(b): a Data Processor or another Data Fiduciary
 * the app shares personal data with.
 *
 * @property string $id
 * @property string $name
 * @property ProcessorKind $kind
 * @property string|null $description
 * @property list<string> $purposes
 * @property list<string> $data_shared
 * @property string|null $contract_reference
 * @property Carbon|null $contract_date
 * @property bool $security_clause
 * @property string|null $country
 * @property bool $hidden_from_access_summary
 * @property bool $active
 */
class Processor extends Model
{
    use HasUlids;

    protected $table = 'data_rights_processors';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'kind',
        'description',
        'purposes',
        'data_shared',
        'contract_reference',
        'contract_date',
        'security_clause',
        'country',
        'hidden_from_access_summary',
        'active',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'security_clause' => false,
        'hidden_from_access_summary' => false,
        'active' => true,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'kind' => ProcessorKind::class,
            'purposes' => 'array',
            'data_shared' => 'array',
            'contract_date' => 'date',
            'security_clause' => 'boolean',
            'hidden_from_access_summary' => 'boolean',
            'active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /** Rule 6(1)(f): a contract with a security clause is expected for every active processor. */
    public function missingContractTerms(): bool
    {
        return blank($this->contract_reference) || ! $this->security_clause;
    }
}
