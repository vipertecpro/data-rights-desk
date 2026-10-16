<?php

namespace Vipertecpro\DataRightsDesk\Http\Controllers\Desk;

use Illuminate\Contracts\View\View;
use Vipertecpro\DataRightsDesk\DataRightsManager;

class OverviewController
{
    public function __invoke(DataRightsManager $dataRights, Summary $summary): View
    {
        return view('data-rights::desk.overview', $summary->overview($dataRights));
    }
}
