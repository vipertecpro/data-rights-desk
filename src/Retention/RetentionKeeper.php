<?php

namespace Vipertecpro\DataRightsDesk\Retention;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Vipertecpro\DataRightsDesk\Concerns\HasRetention;
use Vipertecpro\DataRightsDesk\Enums\ErasureReason;
use Vipertecpro\DataRightsDesk\Enums\RequestStatus;
use Vipertecpro\DataRightsDesk\Events\ErasureWarningSent;
use Vipertecpro\DataRightsDesk\Models\Breach;
use Vipertecpro\DataRightsDesk\Models\BreachSubject;
use Vipertecpro\DataRightsDesk\Models\ConsentRecord;
use Vipertecpro\DataRightsDesk\Models\DataRightsRequest;
use Vipertecpro\DataRightsDesk\Models\GuardianConsent;
use Vipertecpro\DataRightsDesk\Models\LegalHold;
use Vipertecpro\DataRightsDesk\Models\Nomination;
use Vipertecpro\DataRightsDesk\Models\NoticeDelivery;
use Vipertecpro\DataRightsDesk\Models\ProcessingLog;
use Vipertecpro\DataRightsDesk\Models\RequestEvent;
use Vipertecpro\DataRightsDesk\Models\RetentionRun;
use Vipertecpro\DataRightsDesk\Models\SubjectProfile;
use Vipertecpro\DataRightsDesk\Notifications\ErasureWarningNotification;
use Vipertecpro\DataRightsDesk\Requests\RequestDesk;
use Vipertecpro\DataRightsDesk\Support\Languages;
use Vipertecpro\DataRightsDesk\Support\Limits;
use Vipertecpro\DataRightsDesk\Support\RetentionReport;
use Vipertecpro\DataRightsDesk\Support\Sources;
use Vipertecpro\DataRightsDesk\Support\Subjects;

/**
 * Erases what is no longer needed, in a fixed order:
 *
 * 1. keep_days is never below 365 (Rule 8(3), Rule 6(1)(e));
 * 2. processing logs older than keep_days;
 * 3. app models using HasRetention;
 * 4. inactivity erasure (Rule 8(1)-(2), Third Schedule), only when enabled;
 * 5. package records of people erased more than keep_days ago, dated on or
 *    before the erasure (anything newer belongs to a person who came back);
 * 6. requests closed more than keep_days ago;
 * 7. a record of the run, with counts only.
 *
 * Legal holds always win. --pretend counts and changes nothing. Every step
 * works a chunk at a time, so no list of ids grows with the number of people.
 */
class RetentionKeeper
{
    private const CHUNK = 500;

    public function __construct(private RequestDesk $requests) {}

    public function run(bool $pretend = false): RetentionReport
    {
        $startedAt = now();
        $keepDays = Limits::keepDays();
        $cutoff = now()->subDays($keepDays);
        $counts = [];
        $models = [];

        $counts['processing_logs'] = $this->deleteUnlessHeld(ProcessingLog::query()->where('occurred_at', '<', $cutoff), $pretend);

        foreach ((array) config('data-rights.retention.models', []) as $class) {
            if (is_string($class) && class_exists($class) && in_array(HasRetention::class, class_uses_recursive($class), true)) {
                $models[$class] = $this->retentionModel($class, $keepDays, $pretend);
            }
        }

        $counts['model_records'] = array_sum($models);

        [$counts['inactivity_warned'], $counts['inactivity_erased']] = $this->inactivity($pretend);

        $counts['erased_people_records'] = $this->erasedPeopleRecords($cutoff, $pretend);
        $counts['closed_requests'] = $this->deleteUnlessHeld(
            $this->closedRequestsQuery()->whereNotNull('closed_at')->where('closed_at', '<', $cutoff),
            $pretend,
        );

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

    /**
     * Step 3. A hold on the person does not cover these records: hold the records themselves.
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

        $count = 0;
        $morphClass = $model->getMorphClass();

        $query->chunkById(self::CHUNK, function (Collection $records) use ($morphClass, $pretend, &$count): void {
            $held = LegalHold::heldAmong($morphClass, $records->modelKeys());

            foreach ($records as $record) {
                if (isset($held[(string) $record->getKey()])) {
                    continue;
                }

                if (! $pretend) {
                    $this->call($record, 'eraseForRetention');
                }

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
        $query = $class::query();

        // Signing up is approaching the Data Fiduciary too.
        $createdColumn = $model->usesTimestamps() ? $model->getCreatedAtColumn() : null;

        if ($createdColumn !== null) {
            $query->where(fn (Builder $q) => $q->whereNull($model->qualifyColumn($createdColumn))
                ->orWhere($model->qualifyColumn($createdColumn), '<=', $lastSeenCutoff));
        }

        $warned = 0;

        $query->chunkById(self::CHUNK, function (Collection $subjects) use ($morphClass, $years, $warningHours, $countingFrom, $createdColumn, $lastSeenCutoff, $pretend, &$warned): void {
            $keys = $subjects->modelKeys();
            $held = LegalHold::heldAmong($morphClass, $keys);
            $profiles = SubjectProfile::query()
                ->where('subject_type', $morphClass)
                ->whereIn('subject_id', $keys)
                ->get()
                ->keyBy(fn (SubjectProfile $profile): string => (string) $profile->subject_id);

            foreach ($subjects as $subject) {
                $key = (string) $subject->getKey();
                $profile = $profiles->get($key);

                // People seen recently, already warned, already erased, or on legal hold are skipped.
                if (isset($held[$key]) || ($profile !== null && (
                    $profile->erasure_warned_at !== null
                    || $profile->erased_at !== null
                    || ($profile->last_seen_at !== null && $profile->last_seen_at->greaterThan($lastSeenCutoff))
                ))) {
                    continue;
                }

                $warned++;

                if ($pretend) {
                    continue;
                }

                $profile ??= SubjectProfile::for($subject);
                $clock = Carbon::instance($countingFrom);

                foreach ([$profile->last_seen_at, $createdColumn !== null ? $subject->getAttribute($createdColumn) : null] as $candidate) {
                    if ($candidate instanceof CarbonInterface && $candidate->greaterThan($clock)) {
                        $clock = Carbon::instance($candidate);
                    }
                }

                // Third Schedule: three years from the last approach, or the commencement of the Rules, whichever is latest.
                $mark = $clock->copy()->addYears($years);
                $dueAt = $mark->greaterThan(now()->addHours($warningHours)) ? $mark : Carbon::instance(now()->addHours($warningHours));

                $profile->forceFill([
                    'erasure_warned_at' => now(),
                    'erasure_due_at' => $dueAt,
                ])->save();

                Subjects::notify($subject, (new ErasureWarningNotification($dueAt))->locale(Languages::resolve(Subjects::locale($subject))));
                event(new ErasureWarningSent($subject, $dueAt));
            }
        }, $model->getKeyName());

        return $warned;
    }

    private function eraseInactive(int $warningHours, bool $pretend): int
    {
        // Without an Eraser nothing would be erased, so nobody is marked erased.
        if (Sources::erasers() === []) {
            return 0;
        }

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

        $query->chunkById(self::CHUNK, function (Collection $profiles) use (&$erased): void {
            foreach ($profiles as $profile) {
                $subject = Subjects::find($profile->subject_type, $profile->subject_id);

                if ($subject === null) {
                    $profile->forceFill(['erased_at' => now()])->save();

                    continue;
                }

                $report = $this->requests->eraseSubject($subject, ErasureReason::Inactivity);
                $erased += $report->held ? 0 : 1;
            }
        });

        return $erased;
    }

    /**
     * Step 5: the package records of a person erased more than keep_days ago.
     * Only rows dated on or before the erasure go: a person whose account was
     * kept (Third Schedule (a)) or restored can come back, and her newer
     * records stay. The profile goes last, and only when nothing newer is left.
     */
    private function erasedPeopleRecords(CarbonInterface $cutoff, bool $pretend): int
    {
        $count = 0;

        SubjectProfile::query()
            ->whereNotNull('erased_at')
            ->where('erased_at', '<', $cutoff)
            ->chunkById(self::CHUNK, function (Collection $profiles) use ($pretend, &$count): void {
                foreach ($profiles as $profile) {
                    if (LegalHold::coversKey($profile->subject_type, $profile->subject_id) || $profile->erased_at === null) {
                        continue;
                    }

                    $count += $this->eraseRecordsUpTo($profile, $profile->erased_at, $pretend);
                }
            });

        return $count;
    }

    private function eraseRecordsUpTo(SubjectProfile $profile, CarbonInterface $erasedAt, bool $pretend): int
    {
        // Timestamps are compared to the second, so take everything up to the end of that second.
        $until = Carbon::instance($erasedAt)->addSecond();

        $scope = fn (Builder $query): Builder => $query
            ->where('subject_type', $profile->subject_type)
            ->where('subject_id', $profile->subject_id);

        $queries = [
            $scope(ConsentRecord::query())->where('occurred_at', '<', $until),
            $scope(NoticeDelivery::query())->where('delivered_at', '<', $until),
            $scope(Nomination::query())->where(fn (Builder $q) => $q->whereNull('created_at')->orWhere('created_at', '<', $until)),
            $scope(GuardianConsent::query())->where('verified_at', '<', $until),
            $scope(ProcessingLog::query())->where('occurred_at', '<', $until),
            $scope(BreachSubject::query())->whereIn('breach_id', Breach::query()->where('became_aware_at', '<', $until)->select('id')),
        ];

        $requests = $scope($this->closedRequestsQuery())->where('received_at', '<', $until);

        if ($pretend) {
            $count = array_sum(array_map(fn (Builder $query): int => $query->count(), $queries));

            return $count + $this->deleteUnlessHeld($requests, true);
        }

        $count = ConsentRecord::withRetentionDeletes(function () use ($queries): int {
            return array_sum(array_map(fn (Builder $query): int => (int) $query->delete(), $queries));
        });

        $count += $this->deleteUnlessHeld($requests, false);

        if (! $this->hasRecords($profile)) {
            $count += (int) SubjectProfile::query()->whereKey($profile->getKey())->delete();
        }

        return $count;
    }

    /** True when any package record of this person is left. */
    private function hasRecords(SubjectProfile $profile): bool
    {
        foreach ([ConsentRecord::class, NoticeDelivery::class, Nomination::class, GuardianConsent::class, ProcessingLog::class, BreachSubject::class, DataRightsRequest::class] as $class) {
            if ($class::query()->where('subject_type', $profile->subject_type)->where('subject_id', $profile->subject_id)->exists()) {
                return true;
            }
        }

        return false;
    }

    /** @return Builder<DataRightsRequest> */
    private function closedRequestsQuery(): Builder
    {
        return DataRightsRequest::query()
            ->whereIn('status', array_map(fn (RequestStatus $s): string => $s->value, RequestStatus::closed()));
    }

    /**
     * Deletes (or counts) the rows of a package table a chunk at a time, leaving
     * out rows whose subject is on legal hold and requests that are held
     * themselves. Requests take their events with them.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    private function deleteUnlessHeld(Builder $query, bool $pretend): int
    {
        $model = $query->getModel();
        $isRequest = $model instanceof DataRightsRequest;
        $requestMorph = (new DataRightsRequest)->getMorphClass();
        $count = 0;

        $query->clone()
            ->select([$model->getQualifiedKeyName(), $model->qualifyColumn('subject_type'), $model->qualifyColumn('subject_id')])
            ->chunkById(self::CHUNK, function (Collection $rows) use ($model, $isRequest, $requestMorph, $pretend, &$count): void {
                $heldSubjects = $this->heldSubjects($rows);
                $heldRequests = $isRequest ? LegalHold::heldAmong($requestMorph, $rows->modelKeys()) : [];

                $ids = $rows
                    ->reject(fn (Model $row): bool => isset($heldSubjects[$row->getAttribute('subject_type').'|'.$row->getAttribute('subject_id')])
                        || isset($heldRequests[(string) $row->getKey()]))
                    ->modelKeys();

                if ($ids === []) {
                    return;
                }

                if ($pretend) {
                    $count += count($ids);

                    return;
                }

                if ($isRequest) {
                    RequestEvent::query()->whereIn('request_id', $ids)->delete();
                }

                $count += (int) $model->newQuery()->whereKey($ids)->delete();
            }, $model->getKeyName());

        return $count;
    }

    /**
     * The subjects in one chunk of rows that are on legal hold, as "type|id" keys.
     *
     * @param  Collection<int, covariant Model>  $rows
     * @return array<string, true>
     */
    private function heldSubjects(Collection $rows): array
    {
        $held = [];

        foreach ($rows->groupBy(fn (Model $row): string => (string) $row->getAttribute('subject_type')) as $type => $group) {
            if ($type === '') {
                continue;
            }

            $keys = $group->map(fn (Model $row): string => (string) $row->getAttribute('subject_id'))->unique()->values()->all();

            foreach (LegalHold::heldAmong($type, $keys) as $key => $true) {
                $held[$type.'|'.$key] = true;
            }
        }

        return $held;
    }

    /** Calls a HasRetention method; the trait's methods are not on the Model type. */
    private function call(Model $model, string $method, mixed ...$arguments): mixed
    {
        return $model->{$method}(...$arguments);
    }
}
