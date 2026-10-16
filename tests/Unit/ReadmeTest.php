<?php

// The README is the product page on vipertecpro.com, so it follows the product rules:
// no links (relative ones 404 there) except the one badge row of plain shields.io images,
// the not-legal-advice and independence notices, and no claim of official status.

function readme(): string
{
    return (string) file_get_contents(__DIR__.'/../../README.md');
}

function readmeWithoutBadgeRow(): string
{
    $lines = explode("\n", readme());

    return implode("\n", array_filter($lines, fn (string $line): bool => ! str_starts_with($line, '![Latest version](https://img.shields.io/')));
}

it('keeps the README free of links apart from the badge row', function () {
    $text = readmeWithoutBadgeRow();

    expect($text)->not->toMatch('/\]\(/')
        ->and($text)->not->toMatch('#https?://#')
        ->and($text)->not->toMatch('/<a\s/i');
});

it('shows the badge row as plain shields.io images, with no links around them', function () {
    $row = collect(explode("\n", readme()))->first(fn (string $line): bool => str_starts_with($line, '![Latest version]'));

    expect($row)->not->toBeNull();

    preg_match_all('/!\[[^\]]+\]\(([^)]+)\)/', (string) $row, $matches);

    expect($matches[1])->toHaveCount(7)
        ->each->toStartWith('https://img.shields.io/');
    expect((string) $row)->not->toMatch('/\[!\[/');
});

it('carries the not-legal-advice and independence notices', function () {
    expect(readme())
        ->toContain('## Not legal advice')
        ->toContain('not legal advice')
        ->toContain('It is not affiliated with or endorsed by Laravel');
});

it('never claims official, certified or partner status', function () {
    expect(strtolower(readmeWithoutBadgeRow()))
        ->not->toMatch('/\b(official|certified|partner|approved by)\b/');
});
