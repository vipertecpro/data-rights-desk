<?php

namespace Vipertecpro\DataRightsDesk\Notices;

use Illuminate\Support\Facades\DB;
use Vipertecpro\DataRightsDesk\Enums\LanguageStatus;
use Vipertecpro\DataRightsDesk\Events\NoticePublished;
use Vipertecpro\DataRightsDesk\Exceptions\NoticeIncomplete;
use Vipertecpro\DataRightsDesk\Models\Notice;
use Vipertecpro\DataRightsDesk\Support\BuiltNotice;
use Vipertecpro\DataRightsDesk\Support\Languages;

/**
 * Builds, publishes and finds notices (s.5, Rule 3). Every published version
 * is kept, so the ledger can always show the exact text a person saw.
 */
class NoticeRepository
{
    public function __construct(private NoticeBuilder $builder) {}

    /** @return list<string> */
    public function offeredLocales(): array
    {
        return Languages::offered();
    }

    public function build(string $locale): BuiltNotice
    {
        return $this->builder->build($locale);
    }

    /** @return array<string, BuiltNotice> */
    public function buildAll(): array
    {
        $built = [];

        foreach ($this->offeredLocales() as $locale) {
            $built[$locale] = $this->build($locale);
        }

        return $built;
    }

    /**
     * What stops publishing: configured locales whose language status is
     * "needed", and purpose text missing in an offered locale.
     *
     * @return list<string>
     */
    public function problems(): array
    {
        $problems = [];

        foreach (Languages::configured() as $locale) {
            if (Languages::status($locale) === LanguageStatus::Needed) {
                $problems[] = "{$locale}: the package text has no translation yet (lang status \"needed\")";
            }
        }

        foreach ($this->buildAll() as $locale => $notice) {
            foreach ($notice->missing as $field) {
                $problems[] = "{$locale}: {$field} is missing";
            }
        }

        return $problems;
    }

    /**
     * Publishes version N+1 for every offered locale when any locale's text
     * changed or a locale was added. Returns the new version, or null when
     * nothing changed.
     *
     * @throws NoticeIncomplete
     */
    public function publish(): ?int
    {
        $problems = $this->problems();

        if ($problems !== []) {
            throw new NoticeIncomplete($problems);
        }

        $built = $this->buildAll();

        if (! $this->hasChanges($built)) {
            return null;
        }

        $version = ($this->currentVersion() ?? 0) + 1;
        $now = now();

        DB::transaction(function () use ($built, $version, $now): void {
            foreach ($built as $locale => $notice) {
                Notice::query()->create([
                    'version' => $version,
                    'locale' => $locale,
                    'body' => $notice->body,
                    'hash' => $notice->hash,
                    'purposes' => $notice->purposes,
                    'published_at' => $now,
                ]);
            }
        });

        event(new NoticePublished($version, array_keys($built)));

        return $version;
    }

    /** @param  array<string, BuiltNotice>|null  $built */
    public function hasChanges(?array $built = null): bool
    {
        $built ??= $this->buildAll();
        $version = $this->currentVersion();

        if ($version === null) {
            return true;
        }

        $published = Notice::query()->where('version', $version)->pluck('hash', 'locale');

        foreach ($built as $locale => $notice) {
            if (! isset($published[$locale]) || $published[$locale] !== $notice->hash) {
                return true;
            }
        }

        return false;
    }

    /** True when the published notice for this locale differs from what config builds now. */
    public function isStale(string $locale): bool
    {
        $current = $this->current($locale);

        return $current === null || $current->hash !== $this->build($locale)->hash;
    }

    public function currentVersion(): ?int
    {
        $version = Notice::query()->max('version');

        return $version === null ? null : (int) $version;
    }

    /** The latest published notice in this locale. */
    public function current(?string $locale = null): ?Notice
    {
        return Notice::query()
            ->where('locale', $locale ?? Languages::defaultLocale())
            ->orderByDesc('version')
            ->first();
    }

    public function find(int $version, string $locale): ?Notice
    {
        return Notice::query()->where('version', $version)->where('locale', $locale)->first();
    }
}
