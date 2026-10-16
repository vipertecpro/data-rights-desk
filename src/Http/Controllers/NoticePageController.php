<?php

namespace Vipertecpro\DataRightsDesk\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Vipertecpro\DataRightsDesk\Http\Concerns\ResolvesPublicContext;
use Vipertecpro\DataRightsDesk\Notices\NoticeRepository;

/** s.5(1), s.5(3): the notice in the language the person picks. */
class NoticePageController
{
    use ResolvesPublicContext;

    public function __construct(private NoticeRepository $notices) {}

    public function current(Request $request): View
    {
        $locale = $this->pageLocale($request);
        $notice = $this->notices->current($locale) ?? $this->notices->current();

        return view('data-rights::public.notice', [
            'locale' => $locale,
            'notice' => $notice,
            'older' => false,
            'lang' => $this->langParameter($locale),
        ]);
    }

    public function version(Request $request, string $version): View
    {
        $locale = $this->pageLocale($request);
        $notice = $this->notices->find((int) $version, $locale) ?? $this->notices->find((int) $version, config('data-rights.default_locale', 'en'));

        abort_if($notice === null, 404);

        return view('data-rights::public.notice', [
            'locale' => $locale,
            'notice' => $notice,
            'older' => $notice->version < (int) $this->notices->currentVersion(),
            'lang' => $this->langParameter($locale),
        ]);
    }
}
