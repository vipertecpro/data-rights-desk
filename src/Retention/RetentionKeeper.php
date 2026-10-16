<?php

namespace Vipertecpro\DataRightsDesk\Retention;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Vipertecpro\DataRightsDesk\Concerns\HasRetention;
use Vipertecpro\DataRightsDesk\Enums\ErasureReason;
use Vipertecpro\DataRightsDesk\Enums\RequestStatus;
use Vipertecpro\DataRightsDesk\Events\ErasureWarningSent;
use Vipertecpro\DataRightsDesk\Models\BreachSubject;
use Vipertecpro\DataRightsDesk\Models\ConsentRecord;
use Vipertecpro\DataRightsDesk\Models\DataRightsRequest;
use Vipertecpro\DataRightsDesk\Models\GuardianConsent;
use Vipertecpro\DataRightsDesk\Models\LegalHold;
use Vipertecpro\DataRightsDesk\Models\Nomination;
use Vipertecpro\DataRightsDesk\Models\NoticeDelivery;
use Vipertecpro\DataRightsDesk\Models\ProcessingLog;
use Vipertecpro\DataRightsDesk\Models\RetentionRun;
use Vipertecpro\DataRightsDesk\Models\SubjectProfile;
use Vipertecpro\DataRightsDesk\Notifications\ErasureWarningNotification;
use Vipertecpro\DataRightsDesk\Requests\RequestDesk;
use Vipertecpro\DataRightsDesk\Support\Languages;
use Vipertecpro\DataRightsDesk\Support\Limits;
use Vipertecpro\DataRightsDesk\Support\RetentionReport;
use Vipertecpro\DataRightsDesk\Support\Subjects;

/**
 * Erases what is no longer needed, in a fixed order:
 *
 * 1. keep_days is never below 365 (Rule 8(3), Rule 6(1)(e));
 * 2. processing logs older than keep_days;
 * 3. app models using HasRetention;
 * 4. inactivity erasure (Rule 8(1)-(2), Third Schedule), only when enabled;
 * 5. package records of people erased more than keep_days ago;
 * 6. requests closed more than keep_days ago;
 * 7. a record of the run, with counts only.
 *
 * Legal holds always win. --pretend counts and changes nothing.
 */
class RetentionKeeper
{
    public function __construct(private RequestDesk $requests) {}

    public function run(bool $pretend = false): RetentionReport
    {
        $startedAt = now();
        $keepDays = Limits::keepDays();
        $cutoff = now()->subDays($keepDays);
        $counts = [];
        $models = [];

        $counts['processing_logs'] = $this->processingLogs($cutoff, $pretend);

        foreach ((array) config('data-rights.retention.models', []) as $class) {
            if (is_string($class) && class_exists($class) && in_array(HasRetention::class, class_uses_recursive($class), true)) {
                $models[$class] = $this->retentionModel($class, $keepDays, $pretend);
            }
        }

        $counts['model_records'] = array_sum($models);

        [$counts['inactivity_warned'], $counts['inactivity_erased']] = $this->inactivity($pretend);

        $counts['erased_people_records'] = $this->erasedPeopleRecords($cutoff, $pretend);
        $counts['closed_requests'] = $this->closedRequests($cutoff, $pretend);

        $report = new RetentionReport($pretend, $keepDays, $counts, $models);

        RetentionRun::query()->create([
            'started_at' => $startedAt,
            'finished_at' => now(),
            'pretend' => $pretend,
            'summary' => $report->toArray(),
        ]);

        return $report;
    }

    public function placeHold(Model $holdable, string $reason, ?string $lawReference = null, ?CarbonInterface $until = null, ?Model $by = null): LegalHold
    {
        return LegalHold::query()->create([
            'holdable_type' => $holdable->getMorphClass(),
            'holdable_id' => (string) $holdable->getKey(),
            'reason' => $reason,
            'law_reference' => filled($lawReference) ? $lawReference : null,
            'placed_by_type' => $by?->getMorphClass(),
            'placed_by_id' => $by?->getKey(),
            'placed_at' => now(),
            'expires_at' => $until,
        ]);
    }

    public function releaseHold(LegalHold $hold): LegalHold
    {
        if ($hold->released_at === null) {
            $hold->forceFill(['released_at' => now()])->save();
        }

        return $hold;
    }

    /** Step 2. */
    private function processingLogs(CarbonInterface $cutoff, bool $pretend): int
    {
        $query = ProcessingLog::query()->where('occurred_at', '<', $cutoff);
        $this->excludeHeldSubjects($query);

        return $pretend ? $query->count() : (int) $query->delete();
    }

    /**
     * Step 3.
     *
     * @param  class-string<Model>  $class
     */
    private function retentionModel(string $class, int $keepDays, bool $pretend): int
    {
        $model = new $class;
        $days = max((int) $this->call($model, 'retentionDays'), $keepDays);
        $column = (string) $this->call($model, 'retentionDateColumn');

        $query = $this->call($model, 'retentionQuery', $class::query()->where($model->qualifyColumn($column), '<', now()->subDays($days)));

        if (! $query instanceof Builder) {
            return 0;
        }

        $held = LegalHold::heldKeys($model->getMorphClass());

        if ($held !== []) {
            $query->whereNotIn($model->getQualifiedKeyName(), $held);
        }

        if ($pretend) {
            return $query->count();
        }

        $count = 0;

        $query->chunkById(200, function ($records) use (&$count): void {
            foreach ($records as $record) {
                $this->call($record, 'eraseForRetention');
                $count++;
            }
        }, $model->getKeyName());

        return $count;
    }

    /**
     * Step 4: Rule 8(2) warning at least 48 hours before the mark, then
     * erasure (Rule 8(1)) of people who did not come back.
     *
     * @return array{int, int}
     */
    private function inactivity(bool $pretend): array
    {
        if (! (bool) config('data-rights.retention.inactivity.enabled', false)) {
            return [0, 0];
        }

        $years = max(1, (int) config('data-rights.retention.inactivity.years', 3));
        $warningHours = max(48, (int) config('data-rights.retention.inactivity.warning_hours', 48));
        $countingFrom = Date::parse((string) config('data-rights.retention.inactivity.counting_from', '2025-11-13'))->startOfDay();

        return [
            $this->warnInactive($years, $warningHours, $countingFrom, $pretend),
            $this->eraseInactive($warningHours, $pretend),
        ];
    }

    private function warnInactive(int $years, int $warningHours, CarbonInterface $countingFrom, bool $pretend): int
    {
        // A person whose clock (last seen, but never before counting_from) is at
        // or before this moment reaches the mark within the warning window.
        $lastSeenCutoff = now()->addHours($warningHours)->subYears($years);

        if ($countingFrom->greaterThan($lastSeenCutoff)) {
            return 0;
        }

        $class = Subjects::modelClass();

        if (! class_exists($class)) {
            return 0;
        }

        $model = new $class;
        $morphClass = $model->getMorphClass();

        // People seen recently, already warned, or already erased are skipped.
        $skip = SubjectProfile::query()
            ->where('subject_type', $morphClass)
            ->where(fn (Builder $q) => $q->where('last_seen_at', '>', $lastSeenCutoff)
                ->orWhereNotNull('erasure_warned_at')
                ->orWhereNotNull('erased_at'))
            ->pluck('subject_id')
            ->map(fn (mixed $id): string => (string) $id)
            ->merge(LegalHold::heldKeys($morphClass))
            ->unique()
            ->values()
            ->all();

        $query = $class::query();

        if ($skip !== []) {
            $query->whereNotIn($model->getQualifiedKeyName(), $skip);
        }

        // Signing up is approaching the Data Fiduciary too.
        $createdColumn = $model->usesTimestamps() ? $model->getCreatedAtColumn() : null;

        if ($createdColumn !== null) {
            $query->where(fn (Builder $q) => $q->whereNull($model->qualifyColumn($createdColumn))
                ->orWhere($model->qualifyColumn($createdColumn), '<=', $lastSeenCutoff));
        }

        if ($pretend) {
            return $query->count();
        }

        $warned = 0;

        $query->chunkById(200, function ($subjects) use ($years, $warningHours, $countingFrom, $createdColumn, &$warned): void {
            foreach ($subjects as $subject) {
                $profile = SubjectProfile::for($subject);
                $clock = Carbon::instance($countingFrom);

                foreach ([$profile->last_seen_at, $createdColumn !== null ? $subject->getAttribute($createdColumn) : null] as $candidate) {
                    if ($candidate instanceof CarbonInterface && $candidate->greaterThan($clock)) {
                        $clock = Carbon::instance($candidate);
                    }
                }

                // Third Schedule: three years from the last approach, or the commencement of the Rules, whichever is latest.
                $mark = $clock->copy()->addYears($years);
                $dueAt = $mark->greaterThan(now()->addHours($warningHours)) ? $mark : now()->addHours($warningHours);

                $profile->forceFill([
                    'erasure_warned_at' => now(),
                    'erasure_due_at' => $dueAt,
                ])->save();

                Subjects::notify($subject, (new ErasureWarningNotification($dueAt))->locale(Languages::resolve(Subjects::locale($subject))));
                event(new ErasureWarningSent($subject, $dueAt));
                $warned++;
            }
        }, $model->getKeyName());

        return $warned;
    }

    private function eraseInactive(int $warningHours, bool $pretend): int
    {
        $query = SubjectProfile::query()
            ->whereNull('erased_at')
            ->whereNotNull('erasure_warned_at')
            ->where('erasure_warned_at', '<=', now()->subHours($warningHours))
            ->where('erasure_due_at', '<=', now())
            ->where(fn (Builder $q) => $q->whereNull('last_seen_at')->orWhereColumn('last_seen_at', '<', 'erasure_warned_at'));

        if ($pretend) {
            return $query->count();
        }

        $erased = 0;

        foreach ($query->get() as $profile) {
            $subject = Subjects::find($profile->subject_type, $profile->subject_id);

            if ($subject === null) {
                $profile->forceFill(['erased_at' => now()])->save();

                continue;
            }

            $report = $this->requests->eraseSubject($subject, ErasureReason::Inactivity);
            $erased += $report->held ? 0 : 1;
        }

        return $erased;
    }

    /** Step 5. */
    private function erasedPeopleRecords(CarbonInterface $cutoff, bool $pretend): int
    {
        $profiles = SubjectProfile::query()
            ->whereNotNull('erased_at')
            ->where('erased_at', '<', $cutoff)
            ->get(['id', 'subject_type', 'subject_id']);

        $count = 0;

        foreach ($profiles as $profile) {
            if (in_array((string) $profile->subject_id, LegalHold::heldKeys($profile->subject_type), true)) {
                continue;
            }

            $scope = fn (Builder $query): Builder => $query
                ->where('subject_type', $profile->subject_type)
                ->where('subject_id', $profile->subject_id);

            $queries = [
                $scope(ConsentRecord::query()),
                $scope(NoticeDelivery::query()),
                $scope(Nomination::query()),
                $scope(GuardianConsent::query()),
                $scope(ProcessingLog::query()),
                $scope(BreachSubject::query()),
            ];

            $requests = $scope(DataRightsRequest::query())
                ->whereIn('status', array_map(fn (RequestStatus $s): string => $s->value, RequestStatus::closed()));

            $heldRequests = LegalHold::heldKeys((new DataRightsRequest)->getMorphClass());

            if ($heldRequests !== []) {
                $requests->whereNotIn('id', $heldRequests);
            }

            if ($pretend) {
                foreach ($queries as $query) {
                    $count += $query->count();
                }

                $count += $requests->count();

                continue;
            }

            ConsentRecord::withRetentionDeletes(function () use ($queries, $requests, &$count): void {
                foreach ($queries as $query) {
                    $count += (int) $query->delete();
                }

                // Request events go with their requests (cascade).
                $count += (int) $requests->delete();
            });
        }

        return $count;
    }

    /** Step 6. */
    private function closedRequests(CarbonInterface $cutoff, bool $pretend): int
    {
        $query = DataRightsRequest::query()
            ->whereIn('status', array_map(fn (RequestStatus $s): string => $s->value, RequestStatus::closed()))
            ->whereNotNull('closed_at')
            ->where('closed_at', '<', $cutoff);

        $held = LegalHold::heldKeys((new DataRightsRequest)->getMorphClass());

        if ($held !== []) {
            $query->whereNotIn('id', $held);
        }

        $this->excludeHeldSubjects($query);

        return $pretend ? $query->count() : (int) $query->delete();
    }

    /** Calls a HasRetention method; the trait's methods are not on the Model type. */
    private function call(Model $model, string $method, mixed ...$arguments): mixed
    {
        return $model->{$method}(...$arguments);
    }

    /**
     * Leaves out rows whose subject is on legal hold, one morph class at a time.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    private function excludeHeldSubjects(Builder $query): void
    {
        $held = LegalHold::query()->active()->get(['holdable_type', 'holdable_id'])->groupBy('holdable_type');

        foreach ($held as $type => $holds) {
            $ids = $holds->pluck('holdable_id')->map(fn (mixed $id): string => (string) $id)->all();

            $query->where(fn (Builder $q) => $q->whereNull('subject_type')
                ->orWhere('subject_type', '!=', $type)
                ->orWhereNotIn('subject_id', $ids));
        }
    }
}
