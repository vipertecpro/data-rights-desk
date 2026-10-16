<?php

namespace Vipertecpro\DataRightsDesk\Support;

use Carbon\CarbonInterface;

/**
 * Dates as people read them, in the app timezone.
 */
final class Dates
{
    public static function day(?CarbonInterface $date, ?string $locale = null): string
    {
        if ($date === null) {
            return '';
        }

        $date = $date->copy()->setTimezone((string) config('app.timezone', 'UTC'));

        return $locale !== null && $locale !== 'en'
            ? $date->locale($locale)->translatedFormat('j F Y')
            : $date->format('j M Y');
    }

    public static function time(?CarbonInterface $date): string
    {
        if ($date === null) {
            return '';
        }

        return $date->copy()->setTimezone((string) config('app.timezone', 'UTC'))->format('j M Y, H:i');
    }

    /** "Due 14 Jan 2027 (41 days left)" or "Due 14 Jan 2027 (3 days overdue)". */
    public static function due(CarbonInterface $due): string
    {
        $days = (int) floor(now()->diffInDays($due, false));

        $left = match (true) {
            $days > 1 => "{$days} days left",
            $days === 1 => '1 day left',
            $days === 0 && $due->isFuture() => 'due today',
            $days === 0 || $days === -1 => '1 day overdue',
            default => abs($days).' days overdue',
        };

        return 'Due '.self::day($due).' ('.$left.')';
    }

    /** "41 h left" or "6 h overdue". */
    public static function hours(CarbonInterface $due): string
    {
        $hours = (int) floor(now()->diffInHours($due, false));

        return $hours >= 0 ? "{$hours} h left" : abs($hours).' h overdue';
    }
}
