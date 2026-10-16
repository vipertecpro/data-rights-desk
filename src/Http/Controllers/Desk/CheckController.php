<?php

namespace Vipertecpro\DataRightsDesk\Http\Controllers\Desk;

use Illuminate\Contracts\View\View;
use Vipertecpro\DataRightsDesk\DataRightsManager;

class CheckController
{
    public function __invoke(DataRightsManager $dataRights): View
    {
        return view('data-rights::desk.check', ['report' => $dataRights->check()]);
    }
}
