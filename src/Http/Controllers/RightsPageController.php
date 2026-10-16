<?php

namespace Vipertecpro\DataRightsDesk\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Vipertecpro\DataRightsDesk\Http\Concerns\ResolvesPublicContext;
use Vipertecpro\DataRightsDesk\Support\Languages;
use Vipertecpro\DataRightsDesk\Support\Limits;

/**
 * Rule 14(1): how to make a request and the identifier needed; Rule 9: the
 * contact; Rule 14(3): the response period; Rule 3(c)(iii): the Board. No sign-in needed.
 */
class RightsPageController
{
    use ResolvesPublicContext;

    public function __invoke(Request $request): View
    {
        $locale = $this->pageLocale($request);
        $identifier = config('data-rights.requests.identifier');

        if (is_array($identifier)) {
            $identifier = $identifier[$locale] ?? $identifier[Languages::defaultLocale()] ?? null;
        }

        return view('data-rights::public.rights', [
            'locale' => $locale,
            'identifier' => is_string($identifier) && $identifier !== '' ? $identifier : (string) __('data-rights::public.rights.identifier_default'),
            'days' => Limits::responseDays(),
            'boardUrl' => is_string(config('data-rights.board.url')) && config('data-rights.board.url') !== '' ? config('data-rights.board.url') : null,
            'lang' => $this->langParameter($locale),
        ]);
    }
}
