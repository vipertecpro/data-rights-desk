<?php

namespace Vipertecpro\DataRightsDesk\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Str;

/**
 * Notice Markdown helpers. Rendering escapes raw HTML and drops unsafe links.
 */
final class Markdown
{
    public static function toHtml(string $markdown): string
    {
        return (string) Str::markdown($markdown, [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);
    }

    /** The body with the version or draft line placed right under its first heading. */
    public static function document(string $body, string $header): string
    {
        if (str_starts_with($body, '# ')) {
            [$heading, $rest] = array_pad(explode("\n", $body, 2), 2, '');

            return $heading."\n\n".$header."\n".$rest;
        }

        return $header."\n\n".$body;
    }

    public static function noticeHeader(int $version, CarbonInterface $date, string $locale): string
    {
        return (string) __('data-rights::notice.version_line', [
            'version' => $version,
            'date' => Dates::day($date, $locale),
        ], $locale);
    }

    public static function draftHeader(string $locale): string
    {
        return (string) __('data-rights::notice.draft_line', [], $locale);
    }

    /** Escapes characters Markdown would treat as formatting, for text typed by people. */
    public static function escape(string $text): string
    {
        return (string) preg_replace('/([\\\\`*_{}\[\]()#+\-.!|<>])/', '\\\\$1', $text);
    }
}
