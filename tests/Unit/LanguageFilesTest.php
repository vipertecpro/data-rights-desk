<?php

// s.5(3): every language that can be offered has the same strings as English, and keeps every
// :placeholder, so a translation can never drop a part of the notice or break a sentence.

function drdLangStrings(string $locale, string $file): array
{
    $path = __DIR__."/../../lang/{$locale}/{$file}.php";

    if (! is_file($path)) {
        return [];
    }

    $flatten = function (array $items, string $prefix = '') use (&$flatten): array {
        $out = [];

        foreach ($items as $key => $value) {
            $name = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            $out += is_array($value) ? $flatten($value, $name) : [$name => (string) $value];
        }

        return $out;
    };

    return $flatten(include $path);
}

/** @return list<string> */
function drdOfferableLocales(): array
{
    $locales = [];

    foreach (glob(__DIR__.'/../../lang/*/meta.php') as $meta) {
        $locale = basename(dirname($meta));
        $status = (include $meta)['status'] ?? 'needed';

        if ($locale !== 'en' && $status !== 'needed') {
            $locales[] = $locale;
        }
    }

    return $locales;
}

it('s.5(3): every offerable language has every English string and no extra ones', function (string $locale) {
    foreach (['notice', 'public', 'mail'] as $file) {
        $english = drdLangStrings('en', $file);
        $translated = drdLangStrings($locale, $file);

        expect(array_keys(array_diff_key($english, $translated)))->toBe([], "{$locale}/{$file}.php is missing strings")
            ->and(array_keys(array_diff_key($translated, $english)))->toBe([], "{$locale}/{$file}.php has extra strings");

        foreach ($english as $key => $text) {
            expect($translated[$key] ?? '')->not->toBe('', "{$locale}/{$file}.php [{$key}] is empty");
        }
    }
})->with(drdOfferableLocales());

it('s.5(3): every offerable language keeps each :placeholder of the English string, and adds none', function (string $locale) {
    foreach (['notice', 'public', 'mail'] as $file) {
        foreach (drdLangStrings('en', $file) as $key => $text) {
            preg_match_all('/:[a-z_]+/', $text, $expected);
            preg_match_all('/:[a-z_]+/', drdLangStrings($locale, $file)[$key] ?? '', $actual);

            $want = $expected[0];
            $got = $actual[0];
            sort($want);
            sort($got);

            expect($got)->toBe($want, "{$locale}/{$file}.php [{$key}]");
        }
    }
})->with(drdOfferableLocales());

it('s.5(3): every language has a name, a direction and a status', function () {
    foreach (glob(__DIR__.'/../../lang/*/meta.php') as $meta) {
        $data = include $meta;

        expect($data)->toHaveKeys(['status', 'name', 'english_name', 'direction'])
            ->and($data['status'])->toBeIn(['ready', 'needs_review', 'needed'])
            ->and($data['direction'])->toBeIn(['ltr', 'rtl']);
    }
});
