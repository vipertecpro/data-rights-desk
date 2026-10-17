<?php

namespace Vipertecpro\DataRightsDesk\View;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Vipertecpro\DataRightsDesk\Support\Contact;
use Vipertecpro\DataRightsDesk\Support\Languages;

/**
 * Shared data for the public and dashboard layouts.
 */
class LayoutComposer
{
    /** @var array<string, string> */
    private static array $assetVersions = [];

    public function compose(View $view): void
    {
        $locale = app()->getLocale();

        $view->with('drd', [
            'business' => Contact::businessName(),
            'locale' => $locale,
            'dir' => Languages::direction($locale),
            'css' => self::assetUrl('data-rights.css'),
        ]);
    }

    /** The URL of one of the package's stylesheets, versioned by its content; null when the route is off. */
    public static function assetUrl(string $file): ?string
    {
        if (! Route::has('data-rights.asset')) {
            return null;
        }

        return route('data-rights.asset', ['file' => $file, 'v' => self::assetVersion($file)]);
    }

    public static function assetVersion(string $file = 'data-rights.css'): string
    {
        return self::$assetVersions[$file] ??= substr((string) md5_file(__DIR__.'/../../resources/dist/'.$file), 0, 12);
    }
}
