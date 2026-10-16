<?php

namespace Vipertecpro\DataRightsDesk\Requests;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification as Notifier;
use Vipertecpro\DataRightsDesk\Consents\ConsentLedger;
use Vipertecpro\DataRightsDesk\Contracts\IdentityVerifier;
use Vipertecpro\DataRightsDesk\Enums\ErasureReason;
use Vipertecpro\DataRightsDesk\Enums\RequestChannel;
use Vipertecpro\DataRightsDesk\Enums\RequestStatus;
use Vipertecpro\DataRightsDesk\Enums\RequestType;
use Vipertecpro\DataRightsDesk\Events\PersonalDataErased;
use Vipertecpro\DataRightsDesk\Events\RequestReceived;
use Vipertecpro\DataRightsDesk\Events\RequestStatusChanged;
use Vipertecpro\DataRightsDesk\Exceptions\IdentityNotVerified;
use Vipertecpro\DataRightsDesk\Exceptions\InvalidRequest;
use Vipertecpro\DataRightsDesk\Exceptions\InvalidTransition;
use Vipertecpro\DataRightsDesk\Models\DataRightsRequest;
use Vipertecpro\DataRightsDesk\Models\Nomination;
use Vipertecpro\DataRightsDesk\Models\ProcessingLog;
use Vipertecpro\DataRightsDesk\Models\Processor;
use Vipertecpro\DataRightsDesk\Models\RequestEvent;
use Vipertecpro\DataRightsDesk\Models\SubjectProfile;
use Vipertecpro\DataRightsDesk\Notifications\RequestClosedNotification;
use Vipertecpro\DataRightsDesk\Notifications\RequestReceivedNotification;
use Vipertecpro\DataRightsDesk\Purposes\PurposeRegistry;
use Vipertecpro\DataRightsDesk\Support\Contact;
use Vipertecpro\DataRightsDesk\Support\ErasureContext;
use Vipertecpro\DataRightsDesk\Support\ErasureReport;
use Vipertecpro\DataRightsDesk\Support\Languages;
use Vipertecpro\DataRightsDesk\Support\Limits;
use Vipertecpro\DataRightsDesk\Support\Sources;
use Vipertecpro\DataRightsDesk\Support\Subjects;

/**
 * Requests to exercise the rights of s.11 to s.14, each with a clock
 * (Rule 14(3): within a reasonable period not exceeding ninety days).
 */
class RequestDesk
{
    public function __construct(
        private ConsentLedger $consents,
        private PurposeRegistry $purposes,
    ) {}

    public function responseDays(): int
    {
        return Limits::responseDays();
    }

    /**
     * @param  array{channel?: RequestChannel|string, locale?: string|null, actor?: Model|null, received_at?: CarbonInterface|null}  $options
     */
    public function open(RequestType $type, ?Model $subject, ?string $details, array $options = []): DataRightsRequest
    {
        $channel = $options['channel'] ?? RequestChannel::Web;
        $channel = $channel instanceof RequestChannel ? $channel : RequestChannel::from((string) $channel);
        $locale = Languages::resolve($options['locale'] ?? null, $subject !== null ? Subjects::locale($subject) : null);
        $receivedAt = $options['received_at'] ?? now();
        $receivedAt = $receivedAt->isFuture() ? now() : $receivedAt;
        $actor = $options['actor'] ?? null;

        $request = DB::transaction(function () use ($type, $subject, $details, $channel, $locale, $receivedAt, $actor): DataRightsRequest {
            $request = DataRightsRequest::query()->create([
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'type' => $type,
                'status' => RequestStatus::Received,
                'details' => filled($details) ? $details : null,
                'channel' => $channel,
                'locale' => $locale,
                'received_at' => $receivedAt,
                'due_at' => $receivedAt->copy()->addDays($this->responseDays()),
            ]);

            $this->logEvent($request, null, RequestStatus::Received, $actor);

            return $request;
        });

        if ($subject !== null) {
            // Rule 8(1): exercising a right counts as approaching the Data Fiduciary.
            SubjectProfile::markActive($subject);

            $method = app(IdentityVerifier::class)->verify($request, $actor);

            if ($method !== null) {
                $this->verifyIdentity($request, $method, $actor);
            }
        }

        event(new RequestReceived($request));

        if ($subject !== null && $this->notifies()) {
            Subjects::notify($subject, (new RequestReceivedNotification($request))->locale($this->notificationLocale($request, $subject)));
        }

        return $request;
    }

    /** Rule 14(1)(b): record how the person's identity was checked. */
    public function verifyIdentity(DataRightsRequest $request, string $method, ?Model $actor = null): DataRightsRequest
    {
        $request->forceFill([
            'identity_verified_at' => now(),
            'identity_method' => mb_substr($method, 0, 120),
        ])->save();

        $this->logEvent($request, $request->status, $request->status, $actor, 'Identity checked: '.mb_substr($method, 0, 120));

        return $request;
    }

    public function transition(DataRightsRequest $request, RequestStatus $to, ?Model $actor = null, ?string $note = null): DataRightsRequest
    {
        $from = $request->status;

        if (! $from->canMoveTo($to)) {
            throw InvalidTransition::between((string) $request->getKey(), $from, $to);
        }

        $request->forceFill([
            'status' => $to,
            'closed_at' => $to->isClosed() ? now() : null,
        ])->save();

        $this->logEvent($request, $from, $to, $actor, $note);

        event(new RequestStatusChanged($request, $from, $to));

        return $request;
    }

    public function complete(DataRightsRequest $request, string $response, ?Model $actor = null): DataRightsRequest
    {
        if ($request->type === RequestType::Erasure) {
            throw InvalidRequest::erasureNeedsErase((string) $request->getKey());
        }

        if ($request->type !== RequestType::Grievance && ! $request->identityVerified()) {
            throw IdentityNotVerified::forRequest((string) $request->getKey());
        }

        return $this->close($request, RequestStatus::Completed, $response, $actor);
    }

    public function reject(DataRightsRequest $request, string $response, ?Model $actor = null): DataRightsRequest
    {
        return $this->close($request, RequestStatus::Rejected, $response, $actor);
    }

    /** The person cancels her own request. */
    public function cancel(DataRightsRequest $request, ?Model $actor = null): DataRightsRequest
    {
        return $this->transition($request, RequestStatus::Cancelled, $actor);
    }

    /**
     * s.11(1): a summary of the personal data, the processing activities, the
     * Data Fiduciaries and Data Processors it was shared with (minus those
     * s.11(2) excludes), and the contact block.
     *
     * @return array<string, mixed>
     */
    public function accessSummary(Model $subject): array
    {
        $keepDays = Limits::keepDays();
        $personalData = [];

        foreach (Sources::exporters() as $exporter) {
            $personalData[] = [
                'source' => $exporter->name(),
                'processing' => $exporter->processing(),
                'records' => $exporter->export($subject),
            ];
        }

        $logs = ProcessingLog::query()
            ->forSubject($subject)
            ->where('occurred_at', '>=', now()->subDays($keepDays))
            ->orderBy('occurred_at')
            ->get();

        $current = $this->consents->current($subject);
        $personPurposes = [];

        foreach (array_keys($current) as $purpose) {
            if ($this->consents->allows($subject, $purpose)) {
                $personPurposes[] = $purpose;
            }
        }

        foreach ($logs as $log) {
            if ($this->purposes->has($log->purpose)) {
                $personPurposes[] = $log->purpose;
            }
        }

        $personPurposes = array_values(array_unique($personPurposes));

        $sharedWith = Processor::query()
            ->active()
            ->where('hidden_from_access_summary', false)
            ->orderBy('name')
            ->get()
            ->filter(fn (Processor $processor): bool => array_intersect($processor->purposes, $personPurposes) !== [])
            ->map(fn (Processor $processor): array => [
                'name' => $processor->name,
                'kind' => $processor->kind->value,
                'description' => $processor->description,
                'purposes' => array_values(array_intersect($processor->purposes, $personPurposes)),
                'data_shared' => $processor->data_shared,
                'country' => $processor->country,
            ])
            ->values()
            ->all();

        return [
            'generated_at' => now()->toIso8601String(),
            'business' => [
                'name' => Contact::businessName(),
                'website' => Contact::details()['website'],
            ],
            'personal_data' => $personalData,
            'processing_activities' => $logs->map(fn (ProcessingLog $log): array => [
                'purpose' => $log->purpose,
                'activity' => $log->activity,
                'occurred_at' => $log->occurred_at->toIso8601String(),
            ])->values()->all(),
            'consents' => [
                'current' => $current,
                'history' => $this->consents->history($subject)->map(fn ($record): array => [
                    'purpose' => $record->purpose,
                    'action' => $record->action->value,
                    'channel' => $record->channel->value,
                    'given_by' => $record->given_by->value,
                    'occurred_at' => $record->occurred_at->toIso8601String(),
                ])->values()->all(),
            ],
            'shared_with' => $sharedWith,
            'contact' => Contact::details(),
        ];
    }

    /**
     * s.12(3), s.8(7): runs every Eraser for the person, stores the report,
     * marks her erased and sends the closing reply to the address captured
     * before erasure. A legal hold on the person stops it; the report says so.
     */
    public function erase(DataRightsRequest $request, ?Model $actor = null): ErasureReport
    {
        $id = (string) $request->getKey();

        if ($request->type !== RequestType::Erasure) {
            throw InvalidRequest::notErasure($id);
        }

        if ($request->isClosed()) {
            throw InvalidTransition::between($id, $request->status, RequestStatus::Completed);
        }

        if (! $request->identityVerified()) {
            throw IdentityNotVerified::forRequest($id);
        }

        $subject = $request->subject;

        if (! $subject instanceof Model) {
            throw InvalidRequest::withoutSubject($id);
        }

        if (Sources::erasers() === []) {
            throw InvalidRequest::noErasers();
        }

        $email = Subjects::email($subject);
        $locale = $this->notificationLocale($request, $subject);
        $report = $this->eraseSubject($subject, ErasureReason::Request, $request);

        $request->forceFill(['result' => $report->toArray()])->save();

        if ($report->held) {
            if ($request->status === RequestStatus::Received) {
                $this->transition($request, RequestStatus::InProgress, $actor, 'Erasure stopped by a legal hold.');
            }

            return $report;
        }

        $lines = [(string) __('data-rights::mail.erasure.done', [], $locale)];

        if ($report->retainedReasons() !== []) {
            $lines[] = (string) __('data-rights::mail.erasure.retained', ['reasons' => implode('; ', $report->retainedReasons())], $locale);
        }

        $lines[] = (string) __('data-rights::mail.erasure.logs', [], $locale);

        $this->close($request, RequestStatus::Completed, implode("\n\n", $lines), $actor, notify: false);

        if ($email !== null && $this->notifies()) {
            Notifier::route('mail', $email)->notify((new RequestClosedNotification($request))->locale($locale));
        }

        return $report;
    }

    /**
     * Runs every Eraser for one person. Shared by erasure requests and the
     * retention keeper's inactivity step. Her other open requests are
     * cancelled with a note, since there is nothing left to act on.
     *
     * @throws InvalidRequest when no Eraser is configured
     */
    public function eraseSubject(Model $subject, ErasureReason $reason, ?DataRightsRequest $except = null): ErasureReport
    {
        if (Sources::erasers() === []) {
            throw InvalidRequest::noErasers();
        }

        $context = new ErasureContext($reason, $subject, Limits::keepDays());

        if ($context->subjectIsHeld()) {
            return ErasureReport::blockedByHold($reason);
        }

        $outcomes = [];

        foreach (Sources::erasers() as $eraser) {
            $outcomes[$eraser->name()] = $eraser->erase($subject, $context);
        }

        // s.14: nominations go with the person, at once.
        $nominations = Nomination::query()->forSubject($subject)->delete();

        SubjectProfile::for($subject)->forceFill([
            'erased_at' => now(),
            'erasure_warned_at' => null,
            'erasure_due_at' => null,
        ])->save();

        $report = new ErasureReport($reason, $outcomes, false, (int) $nominations);

        DataRightsRequest::query()
            ->forSubject($subject)
            ->open()
            ->when($except !== null, fn ($query) => $query->whereKeyNot($except?->getKey()))
            ->get()
            ->each(fn (DataRightsRequest $open) => $this->transition($open, RequestStatus::Cancelled, null, 'Cancelled because the person\'s data was erased.'));

        event(new PersonalDataErased($subject->getMorphClass(), $subject->getKey(), $report));

        return $report;
    }

    private function close(DataRightsRequest $request, RequestStatus $status, string $response, ?Model $actor, bool $notify = true): DataRightsRequest
    {
        $request->forceFill(['response' => $response]);
        $this->transition($request, $status, $actor);

        $subject = $request->subject;

        if ($notify && $subject instanceof Model && $this->notifies()) {
            Subjects::notify($subject, (new RequestClosedNotification($request))->locale($this->notificationLocale($request, $subject)));
        }

        return $request;
    }

    private function logEvent(DataRightsRequest $request, ?RequestStatus $from, RequestStatus $to, ?Model $actor, ?string $note = null): void
    {
        RequestEvent::query()->create([
            'request_id' => $request->getKey(),
            'from_status' => $from,
            'to_status' => $to,
            'actor_type' => $actor?->getMorphClass(),
            'actor_id' => $actor?->getKey(),
            'note' => filled($note) ? $note : null,
        ]);
    }

    private function notifies(): bool
    {
        return (bool) config('data-rights.requests.notify', true);
    }

    private function notificationLocale(DataRightsRequest $request, Model $subject): string
    {
        return Languages::resolve(Subjects::locale($subject), $request->locale);
    }
}
