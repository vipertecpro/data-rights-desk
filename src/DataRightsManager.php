<?php

namespace Vipertecpro\DataRightsDesk;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Vipertecpro\DataRightsDesk\Breaches\BreachDesk;
use Vipertecpro\DataRightsDesk\Checker\Checker;
use Vipertecpro\DataRightsDesk\Children\ChildGuard;
use Vipertecpro\DataRightsDesk\Consents\ConsentLedger;
use Vipertecpro\DataRightsDesk\Enums\ConsentChannel;
use Vipertecpro\DataRightsDesk\Models\ConsentRecord;
use Vipertecpro\DataRightsDesk\Models\GuardianConsent;
use Vipertecpro\DataRightsDesk\Models\Notice;
use Vipertecpro\DataRightsDesk\Models\NoticeDelivery;
use Vipertecpro\DataRightsDesk\Models\ProcessingLog;
use Vipertecpro\DataRightsDesk\Nominations\NominationBook;
use Vipertecpro\DataRightsDesk\Notices\NoticeRepository;
use Vipertecpro\DataRightsDesk\Purposes\PurposeRegistry;
use Vipertecpro\DataRightsDesk\Requests\RequestDesk;
use Vipertecpro\DataRightsDesk\Retention\RetentionKeeper;
use Vipertecpro\DataRightsDesk\Support\CheckReport;
use Vipertecpro\DataRightsDesk\Support\Contact;

/**
 * The root behind the DataRights facade. Helps an app meet the duties of a
 * Data Fiduciary under the DPDP Act, 2023 and the DPDP Rules, 2025.
 */
class DataRightsManager
{
    public function __construct(protected Container $app) {}

    /** s.4, s.6, s.7, s.9: may this purpose be processed for this person now? */
    public function allows(Model $subject, string $purpose): bool
    {
        return $this->consents()->allows($subject, $purpose);
    }

    public function denies(Model $subject, string $purpose): bool
    {
        return ! $this->allows($subject, $purpose);
    }

    /** @param  array<string, mixed>  $options  channel, locale, ip, user_agent, given_by, guardian_consent */
    public function give(Model $subject, string $purpose, array $options = []): ConsentRecord
    {
        return $this->consents()->give($subject, $purpose, $options);
    }

    /**
     * Record the purposes ticked in <x-data-rights::consent-fields> (or your own form that posts
     * data_rights[purposes][] and data_rights[notice]), against the notice version the form showed.
     *
     * @return list<ConsentRecord>
     */
    public function giveFromForm(Model $subject, Request $request, string $field = 'data_rights'): array
    {
        $purposes = array_values(array_filter((array) $request->input($field.'.purposes', []), 'is_string'));
        $notice = $request->input($field.'.notice');
        $locale = $request->input($field.'.locale');

        return $this->consents()->giveMany($subject, $purposes, array_filter([
            'channel' => ConsentChannel::Web,
            'notice' => is_string($notice) ? $notice : null,
            'locale' => is_string($locale) ? $locale : null,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ], fn (mixed $value): bool => $value !== null));
    }

    /** @param  array<string, mixed>  $options  channel, locale, ip, user_agent, given_by, guardian_consent */
    public function withdraw(Model $subject, string $purpose, array $options = []): ConsentRecord
    {
        return $this->consents()->withdraw($subject, $purpose, $options);
    }

    /**
     * Rule 8(3), s.11(1)(a): logs one processing activity. Use short labels
     * like "order.shipped"; never personal data.
     */
    public function logProcessing(Model $subject, string $purpose, string $activity): void
    {
        $this->purposes()->get($purpose);

        ProcessingLog::query()->create([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'purpose' => $purpose,
            'activity' => mb_substr($activity, 0, 120),
            'occurred_at' => now(),
        ]);
    }

    /**
     * s.6(10): every ledger row for the person (and purpose), the text and hash
     * of each notice those rows reference, and the notice deliveries.
     *
     * @return array<string, mixed>
     */
    public function proof(Model $subject, ?string $purpose = null): array
    {
        if ($purpose !== null) {
            $this->purposes()->get($purpose);
        }

        $records = $this->consents()->history($subject, $purpose);
        $deliveries = NoticeDelivery::query()->forSubject($subject)->orderBy('delivered_at')->get();

        $noticeIds = $records->pluck('notice_id')
            ->merge($deliveries->pluck('notice_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $notices = Notice::query()->whereIn('id', $noticeIds)->orderBy('version')->orderBy('locale')->get();

        return [
            'generated_at' => now()->toIso8601String(),
            'business' => Contact::businessName(),
            'subject' => [
                'type' => $subject->getMorphClass(),
                'id' => $subject->getKey(),
            ],
            'purpose' => $purpose,
            'consents' => $records->map(fn (ConsentRecord $record): array => [
                'id' => $record->getKey(),
                'purpose' => $record->purpose,
                'action' => $record->action->value,
                'occurred_at' => $record->occurred_at->format('Y-m-d\TH:i:s.uP'),
                'channel' => $record->channel->value,
                'given_by' => $record->given_by->value,
                'guardian_consent_id' => $record->guardian_consent_id,
                'consent_manager' => $record->consent_manager,
                'external_reference' => $record->external_reference,
                'locale' => $record->locale,
                'notice_id' => $record->notice_id,
                'notice_hash' => $record->notice_hash,
                'ip_address' => $record->ip_address,
                'user_agent' => $record->user_agent,
            ])->values()->all(),
            'notices' => $notices->map(fn (Notice $notice): array => [
                'id' => $notice->getKey(),
                'version' => $notice->version,
                'locale' => $notice->locale,
                'published_at' => $notice->published_at->toIso8601String(),
                'hash' => $notice->hash,
                'hash_matches_body' => $notice->hashMatches(),
                'body' => $notice->body,
            ])->values()->all(),
            'deliveries' => $deliveries->map(fn (NoticeDelivery $delivery): array => [
                'notice_id' => $delivery->notice_id,
                'channel' => $delivery->channel->value,
                'delivered_at' => $delivery->delivered_at->toIso8601String(),
            ])->values()->all(),
            'guardian_consents' => GuardianConsent::query()->forSubject($subject)->orderBy('verified_at')->get()
                ->map(fn (GuardianConsent $consent): array => [
                    'id' => $consent->getKey(),
                    'kind' => $consent->kind->value,
                    'method' => $consent->method->value,
                    'verified_at' => $consent->verified_at->toIso8601String(),
                    'verified_by' => $consent->verified_by,
                    'revoked_at' => $consent->revoked_at?->toIso8601String(),
                ])->values()->all(),
        ];
    }

    /**
     * Rule 9: the business contact block.
     *
     * @return array<string, string|null>
     */
    public function contact(): array
    {
        return Contact::details();
    }

    public function purposes(): PurposeRegistry
    {
        return $this->app->make(PurposeRegistry::class);
    }

    public function consents(): ConsentLedger
    {
        return $this->app->make(ConsentLedger::class);
    }

    public function notices(): NoticeRepository
    {
        return $this->app->make(NoticeRepository::class);
    }

    public function requests(): RequestDesk
    {
        return $this->app->make(RequestDesk::class);
    }

    public function nominations(): NominationBook
    {
        return $this->app->make(NominationBook::class);
    }

    public function breaches(): BreachDesk
    {
        return $this->app->make(BreachDesk::class);
    }

    public function children(): ChildGuard
    {
        return $this->app->make(ChildGuard::class);
    }

    public function retention(): RetentionKeeper
    {
        return $this->app->make(RetentionKeeper::class);
    }

    public function check(): CheckReport
    {
        return $this->app->make(Checker::class)->run();
    }
}
