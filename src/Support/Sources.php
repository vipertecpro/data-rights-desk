<?php

namespace Vipertecpro\DataRightsDesk\Support;

use Vipertecpro\DataRightsDesk\Contracts\Eraser;
use Vipertecpro\DataRightsDesk\Contracts\Exporter;
use Vipertecpro\DataRightsDesk\Contracts\PurposeEraser;

/**
 * The app's Exporter and Eraser classes from config('data-rights.sources').
 */
final class Sources
{
    /** @return list<object> */
    public static function all(): array
    {
        $sources = [];

        foreach ((array) config('data-rights.sources', []) as $class) {
            if (is_string($class) && class_exists($class)) {
                $sources[] = app($class);
            } elseif (is_object($class)) {
                $sources[] = $class;
            }
        }

        return $sources;
    }

    /** @return list<Exporter> */
    public static function exporters(): array
    {
        return array_values(array_filter(self::all(), fn (object $source): bool => $source instanceof Exporter));
    }

    /** @return list<Eraser> */
    public static function erasers(): array
    {
        return array_values(array_filter(self::all(), fn (object $source): bool => $source instanceof Eraser));
    }

    /** @return list<PurposeEraser> */
    public static function purposeErasersFor(string $purpose): array
    {
        return array_values(array_filter(
            self::all(),
            fn (object $source): bool => $source instanceof PurposeEraser && in_array($purpose, $source->purposes(), true),
        ));
    }
}
