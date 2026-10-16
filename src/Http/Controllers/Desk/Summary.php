<?php

namespace Vipertecpro\DataRightsDesk\Http\Controllers\Desk;

use Vipertecpro\DataRightsDesk\DataRightsManager;
use Vipertecpro\DataRightsDesk\Models\Breach;
use Vipertecpro\DataRightsDesk\Models\ConsentRecord;
use Vipertecpro\DataRightsDesk\Models\DataRightsRequest;
use Vipertecpro\DataRightsDesk\Models\RetentionRun;

/** Numbers for the overview page and the summary JSON. */
class Summary
{
    /** @return array<string, mixed> */
    public function overview(DataRightsManager $dataRights): array
    {
        $open = DataRightsRequest::query()->open();

        $consents = [];

        foreach (ConsentRecord::query()->selectRaw('purpose, action, count(*) as total')->groupBy('purpose', 'action')->get() as $row) {
            $consents[(string) $row->purpose][$row->action->value] = (int) $row->getAttribute('total');
        }

        $purposes = [];

        foreach ($dataRights->purposes()->all() as $key => $purpose) {
            $purposes[$key] = [
                'title' => $purpose->title(),
                'given' => $consents[$key]['given'] ?? 0,
                'withdrawn' => $consents[$key]['withdrawn'] ?? 0,
            ];
        }

        return [
            'openRequests' => (clone $open)->count(),
            'nearestDue' => (clone $open)->orderBy('due_at')->first()?->due_at,
            'overdueRequests' => DataRightsRequest::query()->overdue()->count(),
            'openBreaches' => Breach::query()->whereNull('board_reported_at')->orderBy('board_report_due_at')->get(),
            'purposes' => $purposes,
            'lastRun' => RetentionRun::query()->orderByDesc('started_at')->first(),
            'noticeVersion' => $dataRights->notices()->currentVersion(),
            'check' => $dataRights->check(),
        ];
    }
}
