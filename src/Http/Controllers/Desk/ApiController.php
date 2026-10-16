<?php

namespace Vipertecpro\DataRightsDesk\Http\Controllers\Desk;

use Illuminate\Http\JsonResponse;
use Vipertecpro\DataRightsDesk\DataRightsManager;
use Vipertecpro\DataRightsDesk\Models\Breach;
use Vipertecpro\DataRightsDesk\Models\DataRightsRequest;
use Vipertecpro\DataRightsDesk\Models\LegalHold;
use Vipertecpro\DataRightsDesk\Models\Notice;
use Vipertecpro\DataRightsDesk\Models\RetentionRun;
use Vipertecpro\DataRightsDesk\Support\Subjects;

/** Read-only dashboard JSON, behind the same gate. */
class ApiController
{
    public function __construct(private DataRightsManager $dataRights, private Summary $summary) {}

    public function summary(): JsonResponse
    {
        $data = $this->summary->overview($this->dataRights);

        return response()->json([
            'open_requests' => $data['openRequests'],
            'nearest_due_at' => $data['nearestDue']?->toIso8601String(),
            'overdue_requests' => $data['overdueRequests'],
            'open_breaches' => $data['openBreaches']->map(fn (Breach $b): array => $this->breachData($b))->values()->all(),
            'consents' => $data['purposes'],
            'last_retention_run' => $data['lastRun']?->started_at?->toIso8601String(),
            'notice_version' => $data['noticeVersion'],
            'check' => ['errors' => count($data['check']->errors()), 'warnings' => count($data['check']->warnings())],
        ]);
    }

    public function requests(): JsonResponse
    {
        return response()->json([
            'data' => DataRightsRequest::query()->orderByDesc('received_at')->limit(500)->get()
                ->map(fn (DataRightsRequest $r): array => $this->requestData($r, false))->values()->all(),
        ]);
    }

    public function request(DataRightsRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->requestData($request, true)]);
    }

    public function consents(): JsonResponse
    {
        return response()->json(['data' => $this->summary->overview($this->dataRights)['purposes']]);
    }

    public function breaches(): JsonResponse
    {
        return response()->json([
            'data' => Breach::query()->orderByDesc('became_aware_at')->limit(500)->get()
                ->map(fn (Breach $b): array => $this->breachData($b))->values()->all(),
        ]);
    }

    public function breach(Breach $breach): JsonResponse
    {
        return response()->json(['data' => [
            ...$this->breachData($breach),
            'affected' => $breach->affected()->count(),
            'notified' => $breach->affected()->whereNotNull('notified_at')->count(),
        ]]);
    }

    public function notices(): JsonResponse
    {
        return response()->json([
            'current_version' => $this->dataRights->notices()->currentVersion(),
            'data' => Notice::query()->orderByDesc('version')->orderBy('locale')->get()->map(fn (Notice $n): array => [
                'id' => $n->getKey(),
                'version' => $n->version,
                'locale' => $n->locale,
                'hash' => $n->hash,
                'published_at' => $n->published_at->toIso8601String(),
            ])->values()->all(),
        ]);
    }

    public function retention(): JsonResponse
    {
        return response()->json([
            'runs' => RetentionRun::query()->orderByDesc('started_at')->limit(20)->get()->map(fn (RetentionRun $run): array => [
                'started_at' => $run->started_at->toIso8601String(),
                'finished_at' => $run->finished_at?->toIso8601String(),
                'pretend' => $run->pretend,
                'summary' => $run->summary,
            ])->values()->all(),
            'active_holds' => LegalHold::query()->active()->count(),
        ]);
    }

    public function check(): JsonResponse
    {
        return response()->json($this->dataRights->check()->toArray());
    }

    /** @return array<string, mixed> */
    private function requestData(DataRightsRequest $request, bool $full): array
    {
        $data = [
            'id' => $request->getKey(),
            'type' => $request->type->value,
            'status' => $request->status->value,
            'channel' => $request->channel->value,
            'received_at' => $request->received_at->toIso8601String(),
            'due_at' => $request->due_at->toIso8601String(),
            'overdue' => $request->isOverdue(),
            'identity_verified_at' => $request->identity_verified_at?->toIso8601String(),
            'closed_at' => $request->closed_at?->toIso8601String(),
        ];

        if ($full) {
            $subject = $request->subject;
            $data['subject'] = $subject !== null ? Subjects::label($subject) : null;
            $data['details'] = $request->details;
            $data['response'] = $request->response;
            $data['result'] = $request->result;
            $data['identity_method'] = $request->identity_method;
        }

        return $data;
    }

    /** @return array<string, mixed> */
    private function breachData(Breach $breach): array
    {
        return [
            'id' => $breach->getKey(),
            'title' => $breach->title,
            'became_aware_at' => $breach->became_aware_at->toIso8601String(),
            'report_due_at' => $breach->reportDueAt()->toIso8601String(),
            'hours_left' => $breach->board_reported_at === null ? $breach->hoursLeft() : null,
            'report_overdue' => $breach->isReportOverdue(),
            'board_alerted_at' => $breach->board_alerted_at?->toIso8601String(),
            'board_reported_at' => $breach->board_reported_at?->toIso8601String(),
        ];
    }
}
