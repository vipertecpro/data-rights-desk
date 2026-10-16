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
    private static ?string $assetVersion = null;

    public function compose(View $view): void
    {
        $locale = app()->getLocale();

        $view->with('drd', [
            'business' => Contact::businessName(),
            'locale' => $locale,
            'dir' => Languages::direction($locale),
            'css' => Route::has('data-rights.asset')
                ? route('data-rights.asset', ['file' => 'data-rights.css', 'v' => self::assetVersion()])
                : null,
        ]);
    }

    public static function assetVersion(): string
    {
        return self::$assetVersion ??= substr((string) md5_file(__DIR__.'/../../resources/dist/data-rights.css'), 0, 12);
    }
}
