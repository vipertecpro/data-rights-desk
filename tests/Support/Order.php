<?php

namespace Vipertecpro\DataRightsDesk\Tests\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Vipertecpro\DataRightsDesk\Concerns\HasRetention;

class Order extends Model
{
    use HasRetention;

    protected $table = 'orders';

    protected $guarded = [];

    public static int $days = 30;

    public function retentionPurpose(): string
    {
        return 'account';
    }

    public function retentionDays(): int
    {
        return static::$days;
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function retentionQuery(Builder $query): Builder
    {
        return $query->where('status', 'closed');
    }
}
