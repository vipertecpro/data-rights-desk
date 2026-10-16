<?php

namespace Vipertecpro\DataRightsDesk\Http\Controllers\Desk;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Vipertecpro\DataRightsDesk\DataRightsManager;
use Vipertecpro\DataRightsDesk\Enums\RequestChannel;
use Vipertecpro\DataRightsDesk\Enums\RequestStatus;
use Vipertecpro\DataRightsDesk\Enums\RequestType;
use Vipertecpro\DataRightsDesk\Exceptions\DataRightsException;
use Vipertecpro\DataRightsDesk\Models\DataRightsRequest;
use Vipertecpro\DataRightsDesk\Support\Subjects;

class RequestController
{
    public function __construct(private DataRightsManager $dataRights) {}

    public function index(Request $http): View
    {
        $filters = $http->validate([
            'status' => ['nullable', Rule::enum(RequestStatus::class)],
            'type' => ['nullable', Rule::enum(RequestType::class)],
            'overdue' => ['nullable', 'boolean'],
        ]);

        $requests = DataRightsRequest::query()
            ->with('subject')
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when((bool) ($filters['overdue'] ?? false), fn ($q) => $q->overdue())
            ->orderByRaw('case when closed_at is null then 0 else 1 end')
            ->orderBy('due_at')
            ->paginate(50)
            ->withQueryString();

        return view('data-rights::desk.requests.index', [
            'requests' => $requests,
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('data-rights::desk.requests.create', ['types' => RequestType::cases()]);
    }

    /** Record a request received by email or phone. */
    public function store(Request $http): RedirectResponse
    {
        $data = $http->validate([
            'email' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(RequestType::class)],
            'details' => ['nullable', 'string', 'max:5000'],
            'received_at' => ['nullable', 'date', 'before_or_equal:now'],
        ]);

        $subject = Subjects::findByEmail((string) $data['email']);

        if ($subject === null) {
            return back()->withInput()->withErrors(['email' => 'No person with that email.']);
        }

        $request = $this->dataRights->requests()->open(
            RequestType::from((string) $data['type']),
            $subject,
            isset($data['details']) ? (string) $data['details'] : null,
            [
                'channel' => RequestChannel::Admin,
                'actor' => $this->actor($http),
                'received_at' => isset($data['received_at']) ? Carbon::parse((string) $data['received_at']) : null,
            ],
        );

        return redirect()->route('data-rights-desk.requests.show', $request)->with('data-rights.status', 'Request recorded.');
    }

    public function show(DataRightsRequest $request): View
    {
        $request->load('events');

        return view('data-rights::desk.requests.show', [
            'request' => $request,
            'subject' => $request->subject,
            'statuses' => RequestStatus::cases(),
        ]);
    }

    public function verify(Request $http, DataRightsRequest $request): RedirectResponse
    {
        $data = $http->validate(['method' => ['required', 'string', 'max:120']]);

        $this->dataRights->requests()->verifyIdentity($request, (string) $data['method'], $this->actor($http));

        return redirect()->route('data-rights-desk.requests.show', $request)->with('data-rights.status', 'Identity check recorded.');
    }

    public function status(Request $http, DataRightsRequest $request): RedirectResponse
    {
        $data = $http->validate([
            'status' => ['required', Rule::in([RequestStatus::Received->value, RequestStatus::InProgress->value])],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        return $this->attempt($request, fn () => $this->dataRights->requests()->transition(
            $request,
            RequestStatus::from((string) $data['status']),
            $this->actor($http),
            isset($data['note']) ? (string) $data['note'] : null,
        ), 'Status changed.');
    }

    public function complete(Request $http, DataRightsRequest $request): RedirectResponse
    {
        $data = $http->validate(['response' => ['required', 'string', 'max:10000']]);

        return $this->attempt($request, fn () => $this->dataRights->requests()->complete($request, (string) $data['response'], $this->actor($http)), 'Request completed. The person was told.');
    }

    public function reject(Request $http, DataRightsRequest $request): RedirectResponse
    {
        $data = $http->validate(['response' => ['required', 'string', 'max:10000']]);

        return $this->attempt($request, fn () => $this->dataRights->requests()->reject($request, (string) $data['response'], $this->actor($http)), 'Request rejected. The person was told why.');
    }

    public function erase(Request $http, DataRightsRequest $request): RedirectResponse
    {
        $http->validate(['confirm' => ['accepted']]);

        return $this->attempt($request, function () use ($request, $http): void {
            $report = $this->dataRights->requests()->erase($request, $this->actor($http));

            if ($report->held) {
                throw new DataRightsException('A legal hold on this person stopped the erasure. Release the hold or reject the request with the reason.');
            }
        }, 'Personal data erased. The person was told.');
    }

    public function summary(DataRightsRequest $request): View
    {
        $subject = $request->subject;

        return view('data-rights::desk.requests.summary', [
            'request' => $request,
            'summary' => $subject instanceof Model ? $this->dataRights->requests()->accessSummary($subject) : null,
        ]);
    }

    private function attempt(DataRightsRequest $request, callable $action, string $message): RedirectResponse
    {
        try {
            $action();
        } catch (DataRightsException $e) {
            return redirect()->route('data-rights-desk.requests.show', $request)->withErrors(['request' => $e->getMessage()]);
        }

        return redirect()->route('data-rights-desk.requests.show', $request)->with('data-rights.status', $message);
    }

    private function actor(Request $http): ?Model
    {
        $user = $http->user();

        return $user instanceof Model ? $user : null;
    }
}
