<?php

namespace Vipertecpro\DataRightsDesk\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Vipertecpro\DataRightsDesk\DataRightsManager;
use Vipertecpro\DataRightsDesk\Enums\ConsentChannel;
use Vipertecpro\DataRightsDesk\Enums\RequestChannel;
use Vipertecpro\DataRightsDesk\Enums\RequestType;
use Vipertecpro\DataRightsDesk\Exceptions\NoticeMissing;
use Vipertecpro\DataRightsDesk\Http\Concerns\ResolvesPublicContext;
use Vipertecpro\DataRightsDesk\Models\DataRightsRequest;
use Vipertecpro\DataRightsDesk\Models\Notice;
use Vipertecpro\DataRightsDesk\Support\Languages;

/** JSON for apps that build their own screens. */
class ApiController
{
    use ResolvesPublicContext;

    public function __construct(private DataRightsManager $dataRights) {}

    public function notice(Request $request): JsonResponse
    {
        $locale = $this->pageLocale($request);
        $notice = $this->dataRights->notices()->current($locale) ?? $this->dataRights->notices()->current();

        if (! $notice instanceof Notice) {
            return response()->json(['message' => 'The notice has not been published yet.'], 404);
        }

        $purposes = [];

        foreach ($this->dataRights->purposes()->consentBased() as $purpose) {
            if (array_key_exists($purpose->key, (array) $notice->purposes)) {
                $purposes[] = $purpose->toArray($notice->locale);
            }
        }

        return response()->json([
            ...$this->noticeMeta($notice),
            'markdown' => $notice->document(),
            'html' => $notice->html(),
            // For a sign-up screen: post the ticked keys as data_rights[purposes][] with data_rights[notice] = id.
            'consent_purposes' => $purposes,
            'languages' => $this->languages(),
        ]);
    }

    public function purposes(Request $request): JsonResponse
    {
        $locale = $this->pageLocale($request);
        $subject = $this->subject($request);
        $notice = $this->dataRights->notices()->current($locale);
        $children = $this->dataRights->children();
        $purposes = [];

        foreach ($this->dataRights->purposes()->all() as $key => $purpose) {
            $purposes[] = [...$purpose->toArray($locale), 'allowed' => $this->dataRights->allows($subject, $key)];
        }

        return response()->json([
            'locale' => $locale,
            'notice' => $notice !== null ? $this->noticeMeta($notice) : null,
            'purposes' => $purposes,
            'children' => [
                'is_child' => $children->isChild($subject),
                'needs_guardian_consent' => $children->needsGuardianConsent($subject),
                'may_track' => $children->mayTrack($subject),
            ],
            'languages' => $this->languages(),
        ]);
    }

    public function give(Request $request, string $purpose): JsonResponse
    {
        return $this->record($request, $purpose, true);
    }

    public function withdraw(Request $request, string $purpose): JsonResponse
    {
        return $this->record($request, $purpose, false);
    }

    public function requests(Request $request): JsonResponse
    {
        $subject = $this->subject($request);

        return response()->json([
            'data' => DataRightsRequest::query()->forSubject($subject)->orderByDesc('received_at')->get()
                ->map(fn (DataRightsRequest $r): array => $this->requestData($r, false))->values()->all(),
        ]);
    }

    public function storeRequest(Request $request): JsonResponse
    {
        $locale = $this->pageLocale($request);
        $subject = $this->subject($request);
        $data = $request->validate([
            'type' => ['required', Rule::enum(RequestType::class)],
            'details' => ['nullable', 'string', 'max:5000'],
        ]);

        $created = $this->dataRights->requests()->open(
            RequestType::from((string) $data['type']),
            $subject,
            isset($data['details']) ? (string) $data['details'] : null,
            ['channel' => RequestChannel::Api, 'locale' => $locale, 'actor' => $subject],
        );

        return response()->json(['data' => $this->requestData($created, true)], 201);
    }

    public function request(Request $http, DataRightsRequest $request): JsonResponse
    {
        $this->ensureOwn($request, $this->subject($http));

        return response()->json(['data' => $this->requestData($request, true)]);
    }

    /** s.11: the access summary, for token clients that cannot open the session-only page. */
    public function download(Request $http, DataRightsRequest $request): JsonResponse
    {
        $subject = $this->subject($http);
        $this->ensureOwn($request, $subject);

        abort_unless($request->downloadable(), 404);

        return response()->json($this->dataRights->requests()->accessSummary($subject));
    }

    private function record(Request $request, string $key, bool $give): JsonResponse
    {
        $locale = $this->pageLocale($request);
        $subject = $this->subject($request);

        if (! $this->dataRights->purposes()->has($key)) {
            return response()->json(['message' => 'Unknown purpose.', 'purpose' => $key], 404);
        }

        $purpose = $this->dataRights->purposes()->get($key);
        $options = ['channel' => ConsentChannel::Api, 'locale' => $locale, 'ip' => $request->ip(), 'user_agent' => $request->userAgent()];

        if ($give && ! $purpose->hasChildExemption()) {
            $kind = $this->dataRights->children()->guardianKindNeeded($subject);

            if ($kind !== null) {
                $guardianConsent = $this->dataRights->children()->activeGuardianConsent($subject, $kind);

                if ($guardianConsent === null) {
                    return response()->json(['message' => (string) __('data-rights::public.preferences.guardian_first'), 'purpose' => $key], 403);
                }

                $options['given_by'] = $kind->givenBy();
                $options['guardian_consent'] = $guardianConsent;
            }
        }

        try {
            $record = $give
                ? $this->dataRights->give($subject, $key, $options)
                : $this->dataRights->withdraw($subject, $key, $options);
        } catch (NoticeMissing) {
            return response()->json(['message' => (string) __('data-rights::public.preferences.notice_missing'), 'purpose' => $key], 409);
        }

        return response()->json([
            'purpose' => $key,
            'action' => $record->action->value,
            'occurred_at' => $record->occurred_at->toIso8601String(),
            'notice_version' => $record->notice?->version,
            'allowed' => $this->dataRights->allows($subject, $key),
        ], $give ? 201 : 200);
    }

    /** @return array<string, mixed> */
    private function requestData(DataRightsRequest $request, bool $full): array
    {
        $data = [
            'id' => $request->getKey(),
            'type' => $request->type->value,
            'status' => $request->status->value,
            'received_at' => $request->received_at->toIso8601String(),
            'due_at' => $request->due_at->toIso8601String(),
            'closed_at' => $request->closed_at?->toIso8601String(),
            'downloadable' => $request->downloadable(),
        ];

        if ($full) {
            $data['details'] = $request->details;
            $data['response'] = $request->response;
            $data['download_url'] = $request->downloadable() && Route::has('data-rights.api.requests.download')
                ? route('data-rights.api.requests.download', $request)
                : null;
        }

        return $data;
    }

    /** @return array<string, mixed> */
    private function noticeMeta(Notice $notice): array
    {
        return [
            'id' => $notice->getKey(),
            'version' => $notice->version,
            'locale' => $notice->locale,
            'hash' => $notice->hash,
            'published_at' => $notice->published_at->toIso8601String(),
            'url' => Route::has('data-rights.notice.version') ? route('data-rights.notice.version', ['version' => $notice->version, ...$this->langParameter($notice->locale)]) : null,
        ];
    }

    /** @return list<array{code: string, name: string, english_name: string, direction: string}> */
    private function languages(): array
    {
        return array_map(fn (string $code): array => [
            'code' => $code,
            'name' => Languages::nativeName($code),
            'english_name' => Languages::englishName($code),
            'direction' => Languages::direction($code),
        ], Languages::offered());
    }
}
