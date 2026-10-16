<?php

namespace Vipertecpro\DataRightsDesk\Children;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Vipertecpro\DataRightsDesk\Contracts\ParentalConsentVerifier;
use Vipertecpro\DataRightsDesk\Enums\AgeStatus;
use Vipertecpro\DataRightsDesk\Enums\GuardianKind;
use Vipertecpro\DataRightsDesk\Events\AgeAnswerChanged;
use Vipertecpro\DataRightsDesk\Events\GuardianConsentRecorded;
use Vipertecpro\DataRightsDesk\Models\GuardianConsent;
use Vipertecpro\DataRightsDesk\Models\SubjectProfile;
use Vipertecpro\DataRightsDesk\Support\GuardianVerification;

/**
 * s.9: children and persons with disability who have a lawful guardian.
 *
 * s.9(1): verifiable consent of the parent or lawful guardian before any
 * processing. s.9(3): no tracking, behavioural monitoring or targeted
 * advertising directed at children. Only the age status is stored, never a
 * birth date.
 */
class ChildGuard
{
    public function enabled(): bool
    {
        return (bool) config('data-rights.children.enabled', true);
    }

    public function recordAge(Model $subject, bool $isAdult): SubjectProfile
    {
        $profile = SubjectProfile::for($subject);
        $profile->forceFill([
            'age_status' => $isAdult ? AgeStatus::Adult : AgeStatus::Child,
            'age_declared_at' => now(),
        ])->save();

        return $profile;
    }

    /**
     * Staff change a person's age answer, with a note saying why. The person's
     * own answer is locked once given; this is the way to correct it.
     */
    public function changeAge(Model $subject, bool $isAdult, string $note, ?Model $by = null): SubjectProfile
    {
        $profile = SubjectProfile::for($subject);
        $from = $profile->age_status;
        $to = $isAdult ? AgeStatus::Adult : AgeStatus::Child;

        $profile->forceFill([
            'age_status' => $to,
            'age_changed_at' => now(),
            'age_changed_by_type' => $by?->getMorphClass(),
            'age_changed_by_id' => $by?->getKey(),
            'age_note' => $note,
        ])->save();

        event(new AgeAnswerChanged($subject, $from, $to, $by));

        return $profile;
    }

    public function isChild(Model $subject): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        $status = SubjectProfile::lookup($subject)?->age_status;

        return $status === AgeStatus::Child
            || ($status === null && (bool) config('data-rights.children.unknown_age_is_child', false));
    }

    public function hasLawfulGuardian(Model $subject): bool
    {
        return (bool) SubjectProfile::lookup($subject)?->has_lawful_guardian;
    }

    /** The kind of consent this person needs from someone else, if any. */
    public function guardianKindNeeded(Model $subject): ?GuardianKind
    {
        return match (true) {
            $this->isChild($subject) => GuardianKind::Parent,
            $this->hasLawfulGuardian($subject) => GuardianKind::LawfulGuardian,
            default => null,
        };
    }

    public function needsGuardianConsent(Model $subject): bool
    {
        $kind = $this->guardianKindNeeded($subject);

        return $kind !== null && $this->activeGuardianConsent($subject, $kind) === null;
    }

    public function activeGuardianConsent(Model $subject, ?GuardianKind $kind = null): ?GuardianConsent
    {
        return GuardianConsent::query()
            ->forSubject($subject)
            ->active()
            ->when($kind !== null, fn ($query) => $query->where('kind', $kind?->value))
            ->latest('verified_at')
            ->first();
    }

    public function recordGuardianConsent(Model $subject, GuardianVerification $verification): GuardianConsent
    {
        if ($verification->guardian !== null && $verification->guardian->is($subject)) {
            throw new InvalidArgumentException('A person cannot be her own parent or guardian.');
        }

        $consent = GuardianConsent::query()->create([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'kind' => $verification->kind,
            'method' => $verification->method,
            'guardian_type' => $verification->guardian?->getMorphClass(),
            'guardian_id' => $verification->guardian?->getKey(),
            'guardian_name' => $verification->guardianName,
            'verification_reference' => $verification->verificationReference,
            'verified_at' => now(),
            'verified_by' => get_class(app(ParentalConsentVerifier::class)),
        ]);

        event(new GuardianConsentRecorded($subject, $consent));

        return $consent;
    }

    public function revokeGuardianConsent(GuardianConsent $consent): GuardianConsent
    {
        if ($consent->revoked_at === null) {
            $consent->forceFill(['revoked_at' => now()])->save();
        }

        return $consent;
    }

    /**
     * s.9(3): false for a child, even with a parent's consent. For a guest the
     * age is unknown, so children.unknown_age_is_child decides.
     */
    public function mayTrack(?Model $subject): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        if ($subject === null) {
            return ! (bool) config('data-rights.children.unknown_age_is_child', false);
        }

        return ! $this->isChild($subject);
    }
}
