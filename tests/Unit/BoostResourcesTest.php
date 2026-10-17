<?php

use Illuminate\Support\Facades\Blade;

// Laravel Boost loads these into an app's AI guidelines and skills, so AI coding tools set the package up correctly.

it('renders the Boost guideline through Blade without compiling the package directives', function () {
    $html = Blade::render(file_get_contents(__DIR__.'/../../resources/boost/guidelines/core.blade.php'));

    expect($html)->toContain('## Data Rights Desk')
        ->toContain("@dataRightsAllows('purpose')")
        ->toContain('<x-data-rights::consent-fields')
        ->toContain('data-rights:check --json')
        ->not->toContain('<?php');
});

it('ships a Boost skill with the required front matter', function () {
    $skill = file_get_contents(__DIR__.'/../../resources/boost/skills/data-rights-desk-setup/SKILL.md');

    expect($skill)->toStartWith("---\nname: data-rights-desk-setup\ndescription: ")
        ->toContain('data-rights:check --json');
});

it('keeps the AI instructions free of compliance claims', function () {
    foreach ([
        __DIR__.'/../../resources/boost/guidelines/core.blade.php',
        __DIR__.'/../../resources/boost/skills/data-rights-desk-setup/SKILL.md',
    ] as $file) {
        $text = strtolower(file_get_contents($file));

        // The words appear only inside "never call the app ..." instructions.
        preg_match_all('/\b(compliant|certified|official)\b/', $text, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as [$word, $offset]) {
            expect(substr($text, max(0, $offset - 80), 80))->toMatch('/never|not/');
        }
    }
});
