<?php

namespace Vipertecpro\DataRightsDesk\Support;

use Vipertecpro\DataRightsDesk\Enums\LanguageStatus;

/**
 * s.5(3), s.6(3): the notice and the consent request in English or any
 * language of the Eighth Schedule. A locale is offered only when its
 * lang/{locale}/meta.php status is not "needed".
 */
final class Languages
{
    public static function defaultLocale(): string
    {
        return (string) config('data-rights.default_locale', 'en');
    }

    /**
     * Locales listed in config, default first.
     *
     * @return list<string>
     */
    public static function configured(): array
    {
        $locales = (array) config('data-rights.locales', []);

        return array_values(array_unique([self::defaultLocale(), ...array_map('strval', $locales)]));
    }

    /**
     * Locales a person can choose: configured and not "needed".
     *
     * @return list<string>
     */
    public static function offered(): array
    {
        return array_values(array_filter(self::configured(), fn (string $locale): bool => self::status($locale) !== LanguageStatus::Needed));
    }

    public static function isOffered(?string $locale): bool
    {
        return $locale !== null && in_array($locale, self::offered(), true);
    }

    /** @return array<string, mixed> */
    public static function meta(string $locale): array
    {
        $meta = app('translator')->get('data-rights::meta', [], $locale, false);

        return is_array($meta) ? $meta : [];
    }

    public static function status(string $locale): LanguageStatus
    {
        return LanguageStatus::tryFrom((string) (self::meta($locale)['status'] ?? 'needed')) ?? LanguageStatus::Needed;
    }

    public static function nativeName(string $locale): string
    {
        return (string) (self::meta($locale)['name'] ?? $locale);
    }

    public static function englishName(string $locale): string
    {
        return (string) (self::meta($locale)['english_name'] ?? $locale);
    }

    public static function direction(string $locale): string
    {
        return (self::meta($locale)['direction'] ?? 'ltr') === 'rtl' ? 'rtl' : 'ltr';
    }

    /** The first candidate that is offered, else the default locale. */
    public static function resolve(mixed ...$candidates): string
    {
        foreach ($candidates as $candidate) {
            if (is_string($candidate) && self::isOffered($candidate)) {
                return $candidate;
            }
        }

        return self::defaultLocale();
    }
}
