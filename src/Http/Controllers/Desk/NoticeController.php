<?php

namespace Vipertecpro\DataRightsDesk\Http\Controllers\Desk;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Vipertecpro\DataRightsDesk\DataRightsManager;
use Vipertecpro\DataRightsDesk\Exceptions\NoticeIncomplete;
use Vipertecpro\DataRightsDesk\Models\Notice;
use Vipertecpro\DataRightsDesk\Support\Languages;

class NoticeController
{
    public function __construct(private DataRightsManager $dataRights) {}

    public function index(): View
    {
        $notices = $this->dataRights->notices();
        $locales = [];

        foreach ($notices->offeredLocales() as $locale) {
            $current = $notices->current($locale);
            $locales[] = [
                'code' => $locale,
                'name' => Languages::englishName($locale),
                'status' => Languages::status($locale)->value,
                'current' => $current,
                'stale' => $notices->isStale($locale),
            ];
        }

        return view('data-rights::desk.notices.index', [
            'versions' => Notice::query()->orderByDesc('version')->orderBy('locale')->get()->groupBy('version'),
            'locales' => $locales,
            'problems' => $notices->problems(),
            'hasChanges' => $notices->problems() === [] && $notices->hasChanges(),
            'currentVersion' => $notices->currentVersion(),
        ]);
    }

    public function publish(): RedirectResponse
    {
        try {
            $version = $this->dataRights->notices()->publish();
        } catch (NoticeIncomplete $e) {
            return redirect()->route('data-rights-desk.notices.index')->withErrors(['notice' => $e->getMessage()]);
        }

        return redirect()->route('data-rights-desk.notices.index')->with(
            'data-rights.status',
            $version === null ? 'Nothing changed, so no new version was published.' : "Published notice version {$version}.",
        );
    }

    public function show(Notice $notice): View
    {
        return view('data-rights::desk.notices.show', ['notice' => $notice]);
    }
}
