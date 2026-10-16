<?php

namespace Vipertecpro\DataRightsDesk\Support;

/**
 * The numbers the package will not go past.
 */
final class Limits
{
    /** Rule 14(3) sets 90 days for grievances; the package uses it as the ceiling for every request type. */
    public const MAX_RESPONSE_DAYS = 90;

    /** Rule 8(3), Rule 6(1)(e): logs and personal data of processing are kept at least one year. */
    public const MIN_KEEP_DAYS = 365;

    public static function responseDays(): int
    {
        return min(self::MAX_RESPONSE_DAYS, max(1, (int) config('data-rights.requests.response_days', self::MAX_RESPONSE_DAYS)));
    }

    public static function keepDays(): int
    {
        return max(self::MIN_KEEP_DAYS, (int) config('data-rights.retention.keep_days', self::MIN_KEEP_DAYS));
    }

    public static function downloadDays(): int
    {
        return max(1, (int) config('data-rights.requests.download_days', 30));
    }
}
