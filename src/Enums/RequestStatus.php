<?php

namespace Vipertecpro\DataRightsDesk\Enums;

enum RequestStatus: string
{
    case Received = 'received';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function isClosed(): bool
    {
        return in_array($this, [self::Completed, self::Rejected, self::Cancelled], true);
    }

    public function canMoveTo(self $to): bool
    {
        return ! $this->isClosed() && $to !== $this;
    }

    /** @return list<self> */
    public static function open(): array
    {
        return [self::Received, self::InProgress];
    }

    /** @return list<self> */
    public static function closed(): array
    {
        return [self::Completed, self::Rejected, self::Cancelled];
    }

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Received',
            self::InProgress => 'In progress',
            self::Completed => 'Completed',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }
}
