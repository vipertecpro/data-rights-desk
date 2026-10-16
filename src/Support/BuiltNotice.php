<?php

namespace Vipertecpro\DataRightsDesk\Support;

use Carbon\CarbonInterface;

/**
 * A notice built from config and the language files, before or without publishing.
 */
final readonly class BuiltNotice
{
    /**
     * @param  array<string, mixed>  $purposes  snapshot of the purposes used
     * @param  list<string>  $missing  fields without text in this locale
     */
    public function __construct(
        public string $locale,
        public string $body,
        public string $hash,
        public array $purposes,
        public array $missing,
    ) {}

    public function complete(): bool
    {
        return $this->missing === [];
    }

    /** The standalone Markdown document; a draft header when no version is given. */
    public function document(?int $version = null, ?CarbonInterface $date = null): string
    {
        $header = $version === null || $date === null
            ? Markdown::draftHeader($this->locale)
            : Markdown::noticeHeader($version, $date, $this->locale);

        return Markdown::document($this->body, $header);
    }

    public function html(?int $version = null, ?CarbonInterface $date = null): string
    {
        return Markdown::toHtml($this->document($version, $date));
    }
}
