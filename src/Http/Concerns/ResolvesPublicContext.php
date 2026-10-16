<?php

namespace Vipertecpro\DataRightsDesk\Http\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Vipertecpro\DataRightsDesk\Support\Languages;
use Vipertecpro\DataRightsDesk\Support\Subjects;

trait ResolvesPublicContext
{
    /** ?lang= (or a lang form field), then the person's language, then the app locale, then the default. */
    protected function pageLocale(Request $request): string
    {
        $user = $request->user();

        $locale = Languages::resolve(
            $request->query('lang'),
            $request->input('lang'),
            $user instanceof Model ? Subjects::locale($user) : null,
            app()->getLocale(),
        );

        app()->setLocale($locale);

        return $locale;
    }

    protected function subject(Request $request): Model
    {
        $user = $request->user();

        abort_unless($user instanceof Model, 401);

        return $user;
    }

    /** A person reaches only her own records. */
    protected function ensureOwn(Model $record, Model $subject): void
    {
        abort_unless(method_exists($record, 'isFor') && $record->isFor($subject), 403);
    }

    /** @return array<string, string> */
    protected function langParameter(string $locale): array
    {
        return $locale === Languages::defaultLocale() ? [] : ['lang' => $locale];
    }
}
