<?php

namespace Vipertecpro\DataRightsDesk\Support;

use Vipertecpro\DataRightsDesk\Enums\Basis;
use Vipertecpro\DataRightsDesk\Exceptions\InvalidPurposeDefinition;

/**
 * One purpose of processing, read from config('data-rights.purposes').
 *
 * Rule 3(b): the notice gives an itemised description of the personal data and
 * the specified purpose, with a specific description of the goods or services
 * provided or uses enabled.
 */
final readonly class Purpose
{
    public const KEY_PATTERN = '/^[a-z0-9_.-]{1,64}$/';

    /** @var list<string> */
    public const TEXT_FIELDS = ['title', 'description', 'goods_or_services'];

    /** s.7 clauses (a) to (i). */
    public const LEGITIMATE_USE_CLAUSES = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i'];

    /**
     * @param  array<string, string>  $title
     * @param  array<string, string>  $description
     * @param  array<string, string>  $goodsOrServices
     * @param  array<string, list<string>>  $data
     * @param  array<string, string>  $withdrawalEffect
     */
    public function __construct(
        public string $key,
        public array $title,
        public array $description,
        public array $goodsOrServices,
        public array $data,
        public Basis $basis,
        public ?string $legitimateUse,
        public bool $tracking,
        public ?string $fourthSchedule,
        public array $withdrawalEffect,
        public string $defaultLocale,
    ) {}

    /** @param  array<string, mixed>  $definition */
    public static function fromConfig(string $key, array $definition, string $defaultLocale): self
    {
        if (preg_match(self::KEY_PATTERN, $key) !== 1) {
            throw InvalidPurposeDefinition::because($key, 'keys use only a-z, 0-9, dot, dash and underscore, up to 64 characters.');
        }

        $basis = Basis::tryFrom((string) ($definition['basis'] ?? 'consent'));

        if ($basis === null) {
            throw InvalidPurposeDefinition::because($key, "basis must be 'consent' or 'legitimate_use'.");
        }

        $clause = isset($definition['legitimate_use']) ? strtolower((string) $definition['legitimate_use']) : null;

        if ($basis === Basis::LegitimateUse && ! in_array($clause, self::LEGITIMATE_USE_CLAUSES, true)) {
            throw InvalidPurposeDefinition::because($key, "a legitimate_use purpose needs the s.7 clause, 'a' to 'i'.");
        }

        $fourth = isset($definition['fourth_schedule']) ? strtoupper((string) $definition['fourth_schedule']) : null;

        if ($fourth !== null && ! FourthSchedule::has($fourth)) {
            throw InvalidPurposeDefinition::because($key, 'fourth_schedule must be one of '.implode(', ', FourthSchedule::keys()).'.');
        }

        return new self(
            key: $key,
            title: self::texts($definition['title'] ?? null, $defaultLocale),
            description: self::texts($definition['description'] ?? null, $defaultLocale),
            goodsOrServices: self::texts($definition['goods_or_services'] ?? null, $defaultLocale),
            data: self::lists($definition['data'] ?? null, $defaultLocale),
            basis: $basis,
            legitimateUse: $basis === Basis::LegitimateUse ? $clause : null,
            tracking: (bool) ($definition['tracking'] ?? false),
            fourthSchedule: $fourth,
            withdrawalEffect: self::texts($definition['withdrawal_effect'] ?? null, $defaultLocale),
            defaultLocale: $defaultLocale,
        );
    }

    public function title(?string $locale = null): string
    {
        return $this->pick($this->title, $locale) ?? $this->key;
    }

    public function description(?string $locale = null): string
    {
        return $this->pick($this->description, $locale) ?? '';
    }

    public function goodsOrServices(?string $locale = null): string
    {
        return $this->pick($this->goodsOrServices, $locale) ?? '';
    }

    /** @return list<string> */
    public function data(?string $locale = null): array
    {
        $locale ??= $this->defaultLocale;

        return $this->data[$locale] ?? $this->data[$this->defaultLocale] ?? [];
    }

    public function withdrawalEffect(?string $locale = null): ?string
    {
        return $this->pick($this->withdrawalEffect, $locale);
    }

    /** s.6: the purpose needs the person's consent. */
    public function needsConsent(): bool
    {
        return $this->basis === Basis::Consent;
    }

    public function isLegitimateUse(): bool
    {
        return $this->basis === Basis::LegitimateUse;
    }

    /** s.7(a): data the person gave voluntarily, used until she says she does not consent. */
    public function canBeObjectedTo(): bool
    {
        return $this->legitimateUse === 'a';
    }

    /** A person can press "Withdraw" for this purpose. */
    public function canBeWithdrawn(): bool
    {
        return $this->needsConsent() || $this->canBeObjectedTo();
    }

    /** Rule 12, Fourth Schedule: s.9(1) and s.9(3) do not apply. */
    public function hasChildExemption(): bool
    {
        return $this->fourthSchedule !== null;
    }

    /**
     * Fields that have no text in this locale (no fallback).
     *
     * @return list<string>
     */
    public function missingFor(string $locale): array
    {
        $missing = [];

        foreach (['title' => $this->title, 'description' => $this->description, 'goods_or_services' => $this->goodsOrServices] as $field => $texts) {
            if (trim($texts[$locale] ?? '') === '') {
                $missing[] = "purposes.{$this->key}.{$field}";
            }
        }

        if (($this->data[$locale] ?? []) === []) {
            $missing[] = "purposes.{$this->key}.data";
        }

        if ($this->withdrawalEffect !== [] && trim($this->withdrawalEffect[$locale] ?? '') === '') {
            $missing[] = "purposes.{$this->key}.withdrawal_effect";
        }

        return $missing;
    }

    /**
     * The purpose with its text in one locale.
     *
     * @return array{key: string, title: string, description: string, goods_or_services: string, data: list<string>, basis: string, legitimate_use: string|null, tracking: bool, fourth_schedule: string|null, withdrawal_effect: string|null, can_withdraw: bool}
     */
    public function toArray(?string $locale = null): array
    {
        return [
            'key' => $this->key,
            'title' => $this->title($locale),
            'description' => $this->description($locale),
            'goods_or_services' => $this->goodsOrServices($locale),
            'data' => $this->data($locale),
            'basis' => $this->basis->value,
            'legitimate_use' => $this->legitimateUse,
            'tracking' => $this->tracking,
            'fourth_schedule' => $this->fourthSchedule,
            'withdrawal_effect' => $this->withdrawalEffect($locale),
            'can_withdraw' => $this->canBeWithdrawn(),
        ];
    }

    /**
     * Every locale's text, as stored with a published notice.
     *
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'goods_or_services' => $this->goodsOrServices,
            'data' => $this->data,
            'basis' => $this->basis->value,
            'legitimate_use' => $this->legitimateUse,
            'tracking' => $this->tracking,
            'fourth_schedule' => $this->fourthSchedule,
            'withdrawal_effect' => $this->withdrawalEffect,
        ];
    }

    /** @param  array<string, string>  $texts */
    private function pick(array $texts, ?string $locale): ?string
    {
        $locale ??= $this->defaultLocale;
        $text = $texts[$locale] ?? $texts[$this->defaultLocale] ?? null;

        return $text === null || trim($text) === '' ? null : $text;
    }

    /** @return array<string, string> */
    private static function texts(mixed $value, string $defaultLocale): array
    {
        if (is_string($value)) {
            return [$defaultLocale => $value];
        }

        if (! is_array($value)) {
            return [];
        }

        $texts = [];

        foreach ($value as $locale => $text) {
            if (is_string($locale) && is_string($text)) {
                $texts[$locale] = $text;
            }
        }

        return $texts;
    }

    /** @return array<string, list<string>> */
    private static function lists(mixed $value, string $defaultLocale): array
    {
        if (! is_array($value) || $value === []) {
            return [];
        }

        if (array_is_list($value)) {
            return [$defaultLocale => array_values(array_filter($value, 'is_string'))];
        }

        $lists = [];

        foreach ($value as $locale => $items) {
            if (is_string($locale) && is_array($items)) {
                $lists[$locale] = array_values(array_filter($items, 'is_string'));
            }
        }

        return $lists;
    }
}
