<?php

namespace Vipertecpro\DataRightsDesk\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Vipertecpro\DataRightsDesk\DataRightsManager;
use Vipertecpro\DataRightsDesk\Enums\RequestChannel;
use Vipertecpro\DataRightsDesk\Enums\RequestType;
use Vipertecpro\DataRightsDesk\Http\Concerns\ResolvesPublicContext;
use Vipertecpro\DataRightsDesk\Models\DataRightsRequest;
use Vipertecpro\DataRightsDesk\Support\Limits;

/** s.11 to s.14: the person's own requests and nominations. */
class RequestController
{
    use ResolvesPublicContext;

    public function __construct(private DataRightsManager $dataRights) {}

    public function index(Request $request): View
    {
        $locale = $this->pageLocale($request);
        $subject = $this->subject($request);

        return view('data-rights::public.requests.index', [
            'locale' => $locale,
            'requests' => DataRightsRequest::query()->forSubject($subject)->orderByDesc('received_at')->get(),
            'nominations' => $this->dataRights->nominations()->for($subject),
            'types' => RequestType::cases(),
            'days' => Limits::responseDays(),
            'lang' => $this->langParameter($locale),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $locale = $this->pageLocale($request);
        $subject = $this->subject($request);
        $data = $request->validate([
            'type' => ['required', Rule::enum(RequestType::class)],
            'details' => ['nullable', 'string', 'max:5000'],
        ]);

        $dataRightsRequest = $this->dataRights->requests()->open(
            RequestType::from((string) $data['type']),
            $subject,
            isset($data['details']) ? (string) $data['details'] : null,
            ['channel' => RequestChannel::Web, 'locale' => $locale, 'actor' => $subject],
        );

        return redirect()->route('data-rights.requests.show', ['request' => $dataRightsRequest, ...$this->langParameter($locale)])
            ->with('data-rights.status', (string) __('data-rights::public.requests.created'));
    }

    public function show(Request $http, DataRightsRequest $request): View
    {
        $locale = $this->pageLocale($http);
        $this->ensureOwn($request, $this->subject($http));

        return view('data-rights::public.requests.show', [
            'locale' => $locale,
            'request' => $request,
            'lang' => $this->langParameter($locale),
        ]);
    }

    public function cancel(Request $http, DataRightsRequest $request): RedirectResponse
    {
        $locale = $this->pageLocale($http);
        $subject = $this->subject($http);
        $this->ensureOwn($request, $subject);

        if (! $request->isClosed()) {
            $this->dataRights->requests()->cancel($request, $subject);
        }

        return redirect()->route('data-rights.requests.show', ['request' => $request, ...$this->langParameter($locale)])
            ->with('data-rights.status', (string) __('data-rights::public.requests.cancelled'));
    }

    /** s.11: the access summary, only for completed access requests within download_days. */
    public function download(Request $http, DataRightsRequest $request): JsonResponse
    {
        $subject = $this->subject($http);
        $this->ensureOwn($request, $subject);

        abort_unless($request->downloadable(), 404);

        return response()->json(
            $this->dataRights->requests()->accessSummary($subject),
            200,
            ['Content-Disposition' => 'attachment; filename="data-summary-'.now()->format('Y-m-d').'.json"'],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }
}
