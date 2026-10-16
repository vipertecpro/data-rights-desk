<?php

namespace Vipertecpro\DataRightsDesk\Consents;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Vipertecpro\DataRightsDesk\Children\ChildGuard;
use Vipertecpro\DataRightsDesk\Enums\ConsentAction;
use Vipertecpro\DataRightsDesk\Enums\ConsentChannel;
use Vipertecpro\DataRightsDesk\Enums\GivenBy;
use Vipertecpro\DataRightsDesk\Enums\GuardianKind;
use Vipertecpro\DataRightsDesk\Events\ConsentGiven;
use Vipertecpro\DataRightsDesk\Events\ConsentWithdrawn;
use Vipertecpro\DataRightsDesk\Exceptions\NoticeMissing;
use Vipertecpro\DataRightsDesk\Models\ConsentRecord;
use Vipertecpro\DataRightsDesk\Models\GuardianConsent;
use Vipertecpro\DataRightsDesk\Models\Notice;
use Vipertecpro\DataRightsDesk\Models\SubjectProfile;
use Vipertecpro\DataRightsDesk\Notices\NoticeRepository;
use Vipertecpro\DataRightsDesk\Purposes\PurposeRegistry;
use Vipertecpro\DataRightsDesk\Support\Languages;
use Vipertecpro\DataRightsDesk\Support\Purpose;
use Vipertecpro\DataRightsDesk\Support\Subjects;

/**
 * The append-only consent ledger (s.6). Each give or withdraw adds a row that
 * names the notice the person saw (s.6(10): the Data Fiduciary must be able
 * to prove that a notice was given and consent was given).
 */
class ConsentLedger
{
    public function __construct(
        private PurposeRegistry $purposes,
        private NoticeRepository $notices,
        private ChildGuard $children,
    ) {}

    /**
     * s.6(1): consent for one purpose, from a clear affirmative action.
     *
     * @param  array{channel?: ConsentChannel|string, locale?: string|null, ip?: string|null, user_agent?: string|null, given_by?: GivenBy|string, guardian_consent?: GuardianConsent|string|null}  $options
     *
     * @throws NoticeMissing when no notice is published for the locale (s.5(1))
     */
    public function give(Model $subject, string $purpose, array $options = []): ConsentRecord
    {
        $definition = $this->purposes->get($purpose);
        $locale = $this->localeFor($subject, $options);
        $notice = $this->notices->current($locale);

        if ($notice === null) {
            throw NoticeMissing::forLocale($locale);
        }

        $record = $this->record($subject, $definition, ConsentAction::Given, $notice, $locale, $options);

        // Giving consent is the person approaching the Data Fiduciary (Rule 8(1)); an import is not.
        if (! in_array($record->channel, [ConsentChannel::Import, ConsentChannel::Legacy], true)) {
            SubjectProfile::markActive($subject);
        }

        return $record;
    }

    /**
     * s.6(4): withdraw, the same shape as give. Works even when no notice is published.
     *
     * @param  array{channel?: ConsentChannel|string, locale?: string|null, ip?: string|null, user_agent?: string|null, given_by?: GivenBy|string, guardian_consent?: GuardianConsent|string|null}  $options
     */
    public function withdraw(Model $subject, string $purpose, array $options = []): ConsentRecord
    {
        $definition = $this->purposes->get($purpose);
        $locale = $this->localeFor($subject, $options);

        return $this->record($subject, $definition, ConsentAction::Withdrawn, $this->notices->current($locale), $locale, $options);
    }

    /**
     * s.6(7)-(9): a consent given or withdrawn through a registered Consent Manager.
     */
    public function recordFromConsentManager(
        Model $subject,
        string $purpose,
        ConsentAction $action,
        string $consentManager,
        string $reference,
        ?CarbonInterface $at = null,
    ): ConsentRecord {
        $definition = $this->purposes->get($purpose);
        $locale = $this->localeFor($subject, []);

        return $this->record($subject, $definition, $action, $this->notices->current($locale), $locale, [
            'channel' => ConsentChannel::ConsentManager,
        ], [
            'consent_manager' => $consentManager,
            'external_reference' => $reference,
            'occurred_at' => $at ?? now(),
        ]);
    }

    /**
     * A consent given before this package was installed. s.5(2): send these
     * people the notice with data-rights:notify-existing.
     */
    public function importLegacy(Model $subject, string $purpose, CarbonInterface $givenAt): ConsentRecord
    {
        $definition = $this->purposes->get($purpose);

        return $this->record($subject, $definition, ConsentAction::Given, null, null, [
            'channel' => ConsentChannel::Legacy,
        ], [
            'occurred_at' => $givenAt,
        ]);
    }

    /**
     * Latest state per purpose from the ledger, without the checks for children.
     *
     * @return array<string, bool>
     */
    public function current(Model $subject): array
    {
        $latest = $this->latestRows($subject);
        $state = [];

        foreach ($this->purposes->all() as $key => $purpose) {
            $state[$key] = $this->ledgerAllows($purpose, $latest[$key] ?? null);
        }

        return $state;
    }

    /** @return Collection<int, ConsentRecord> */
    public function history(Model $subject, ?string $purpose = null): Collection
    {
        return ConsentRecord::query()
            ->forSubject($subject)
            ->when($purpose !== null, fn ($query) => $query->where('purpose', $purpose))
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();
    }

    public function latest(Model $subject, string $purpose): ?ConsentRecord
    {
        return ConsentRecord::query()
            ->forSubject($subject)
            ->where('purpose', $purpose)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * s.4, s.6, s.7, s.9: may this purpose be processed for this person now?
     */
    public function allows(Model $subject, string $purpose): bool
    {
        $definition = $this->purposes->get($purpose);
        $latest = $this->latest($subject, $purpose);

        if (! $definition->hasChildExemption()) {
            $kind = $this->children->guardianKindNeeded($subject);

            // s.9(3): no tracking or behavioural monitoring of children, even with a parent's consent.
            if ($kind === GuardianKind::Parent && $definition->tracking) {
                return false;
            }

            // s.9(1), Rules 10-11: verifiable consent of the parent or lawful guardian first,
            // for purposes that rest on consent (s.6) or on data the person gave (s.7(a)).
            // Legitimate uses under s.7(b)-(i) do not depend on anyone's consent.
            if ($kind !== null && ($definition->needsConsent() || $definition->canBeObjectedTo())) {
                if ($this->children->activeGuardianConsent($subject, $kind) === null) {
                    return false;
                }

                if ($definition->needsConsent()) {
                    return $latest !== null && $latest->isGiven() && $latest->given_by === $kind->givenBy();
                }
            }
        }

        return $this->ledgerAllows($definition, $latest);
    }

    /** @return array<string, ConsentRecord> */
    private function latestRows(Model $subject): array
    {
        return $this->history($subject)->keyBy('purpose')->all();
    }

    private function ledgerAllows(Purpose $purpose, ?ConsentRecord $latest): bool
    {
        if ($purpose->needsConsent()) {
            return $latest !== null && $latest->isGiven();
        }

        // s.7(a): until the person indicates she does not consent.
        if ($purpose->canBeObjectedTo()) {
            return $latest === null || $latest->isGiven();
        }

        // s.7(b)-(i): legitimate uses that do not depend on consent.
        return true;
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $extra
     */
    private function record(
        Model $subject,
        Purpose $purpose,
        ConsentAction $action,
        ?Notice $notice,
        ?string $locale,
        array $options,
        array $extra = [],
    ): ConsentRecord {
        $givenBy = $options['given_by'] ?? GivenBy::DataPrincipal;
        $givenBy = $givenBy instanceof GivenBy ? $givenBy : GivenBy::from((string) $givenBy);
        $channel = $options['channel'] ?? ConsentChannel::Web;
        $channel = $channel instanceof ConsentChannel ? $channel : ConsentChannel::from((string) $channel);

        $guardianConsent = $options['guardian_consent'] ?? null;

        if ($guardianConsent === null && $givenBy !== GivenBy::DataPrincipal) {
            $kind = $givenBy === GivenBy::Parent ? GuardianKind::Parent : GuardianKind::LawfulGuardian;
            $guardianConsent = $this->children->activeGuardianConsent($subject, $kind);
        }

        $record = ConsentRecord::query()->create([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'purpose' => $purpose->key,
            'action' => $action,
            'notice_id' => $notice?->getKey(),
            'notice_hash' => $notice?->hash,
            'locale' => $locale,
            'channel' => $channel,
            'given_by' => $givenBy,
            'guardian_consent_id' => $guardianConsent instanceof GuardianConsent ? $guardianConsent->getKey() : $guardianConsent,
            // Data minimisation: IP address and user agent only when the app turns them on.
            'ip_address' => config('data-rights.consent.record_ip') ? ($options['ip'] ?? null) : null,
            'user_agent' => config('data-rights.consent.record_user_agent') ? (mb_substr((string) ($options['user_agent'] ?? ''), 0, 512) ?: null) : null,
            'occurred_at' => now(),
            ...$extra,
        ]);

        event($action === ConsentAction::Given
            ? new ConsentGiven($subject, $purpose->key, $record)
            : new ConsentWithdrawn($subject, $purpose->key, $record));

        return $record;
    }

    /** @param  array<string, mixed>  $options */
    private function localeFor(Model $subject, array $options): string
    {
        $locale = $options['locale'] ?? null;

        if (is_string($locale) && $locale !== '') {
            return $locale;
        }

        return Languages::resolve(Subjects::locale($subject));
    }
}
