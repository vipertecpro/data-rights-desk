<?php

namespace Vipertecpro\DataRightsDesk\Purposes;

use Vipertecpro\DataRightsDesk\Exceptions\UnknownPurpose;
use Vipertecpro\DataRightsDesk\Support\Languages;
use Vipertecpro\DataRightsDesk\Support\Purpose;

/**
 * The purposes from config('data-rights.purposes'). Read on every call, so a
 * config change takes effect at once.
 */
class PurposeRegistry
{
    /** @return array<string, Purpose> */
    public function all(): array
    {
        $purposes = [];
        $default = Languages::defaultLocale();

        foreach ((array) config('data-rights.purposes', []) as $key => $definition) {
            $purposes[(string) $key] = Purpose::fromConfig((string) $key, is_array($definition) ? $definition : [], $default);
        }

        return $purposes;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, (array) config('data-rights.purposes', []));
    }

    public function get(string $key): Purpose
    {
        $definitions = (array) config('data-rights.purposes', []);

        if (! array_key_exists($key, $definitions)) {
            throw UnknownPurpose::key($key);
        }

        return Purpose::fromConfig($key, is_array($definitions[$key]) ? $definitions[$key] : [], Languages::defaultLocale());
    }

    /** @return array<string, Purpose> */
    public function consentBased(): array
    {
        return array_filter($this->all(), fn (Purpose $purpose): bool => $purpose->needsConsent());
    }

    /**
     * Every purpose with its text in one locale.
     *
     * @return array<string, array<string, mixed>>
     */
    public function forLocale(string $locale): array
    {
        return array_map(fn (Purpose $purpose): array => $purpose->toArray($locale), $this->all());
    }

    /** @return list<string> */
    public function keys(): array
    {
        return array_map('strval', array_keys((array) config('data-rights.purposes', [])));
    }
}
