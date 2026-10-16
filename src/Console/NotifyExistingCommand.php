<?php

namespace Vipertecpro\DataRightsDesk\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Vipertecpro\DataRightsDesk\Enums\DeliveryChannel;
use Vipertecpro\DataRightsDesk\Models\Notice;
use Vipertecpro\DataRightsDesk\Models\NoticeDelivery;
use Vipertecpro\DataRightsDesk\Models\SubjectProfile;
use Vipertecpro\DataRightsDesk\Notices\NoticeRepository;
use Vipertecpro\DataRightsDesk\Notifications\NoticeNotification;
use Vipertecpro\DataRightsDesk\Support\Languages;
use Vipertecpro\DataRightsDesk\Support\Subjects;

/**
 * s.5(2): people who consented before the Act's duties began get the notice
 * "as soon as it is reasonably practicable". Sends the current version once
 * per person and records the delivery; safe to run again.
 */
class NotifyExistingCommand extends Command
{
    protected $signature = 'data-rights:notify-existing
        {--before=2027-05-13 : Only people created before this date}
        {--chunk=500 : How many people to load at a time}
        {--pretend : Count without sending}';

    protected $description = 'Send the current notice to existing people who have not received it';

    public function handle(NoticeRepository $notices): int
    {
        $version = $notices->currentVersion();

        if ($version === null) {
            $this->error('No notice is published yet. Run php artisan data-rights:notice first.');

            return self::FAILURE;
        }

        $class = Subjects::modelClass();

        if (! class_exists($class)) {
            $this->error('The subject model in data-rights.subject_model does not exist.');

            return self::FAILURE;
        }

        $before = Carbon::parse((string) $this->option('before'));
        $chunk = max(1, (int) $this->option('chunk'));
        $pretend = (bool) $this->option('pretend');
        $model = new $class;

        $morphClass = $model->getMorphClass();
        $noticeIds = Notice::query()->where('version', $version)->pluck('id')->all();
        $query = $class::query();

        if ($model->usesTimestamps() && $model->getCreatedAtColumn() !== null) {
            $query->where($model->qualifyColumn($model->getCreatedAtColumn()), '<', $before);
        }

        $sent = 0;

        // One chunk at a time: who already has this version, and who was erased, is
        // looked up for the chunk's people only, so nothing grows with the user count.
        $query->chunkById($chunk, function (Collection $subjects) use ($notices, $morphClass, $noticeIds, $pretend, &$sent): void {
            $skip = $this->skipped($morphClass, $subjects->modelKeys(), $noticeIds);

            foreach ($subjects as $subject) {
                if (isset($skip[(string) $subject->getKey()])) {
                    continue;
                }

                if ($pretend) {
                    $sent++;

                    continue;
                }

                $locale = Languages::resolve(Subjects::locale($subject));
                $notice = $notices->current($locale) ?? $notices->current();

                if ($notice === null) {
                    continue;
                }

                $channel = NoticeDelivery::query()->forSubject($subject)->exists()
                    ? DeliveryChannel::Update
                    : DeliveryChannel::Legacy;

                if (! Subjects::notify($subject, (new NoticeNotification($notice))->locale($notice->locale))) {
                    continue;
                }

                NoticeDelivery::query()->create([
                    'notice_id' => $notice->getKey(),
                    'subject_type' => $subject->getMorphClass(),
                    'subject_id' => $subject->getKey(),
                    'channel' => $channel,
                    'delivered_at' => now(),
                ]);

                $sent++;
            }
        }, $model->getKeyName());

        if ($pretend) {
            $this->info("Pretend: {$sent} people would get notice version {$version}.");

            return self::SUCCESS;
        }

        $this->info("Sent notice version {$version} to {$sent} people.");

        return self::SUCCESS;
    }

    /**
     * People in this chunk who already have the current version, or whose data
     * was erased (they are not contacted again).
     *
     * @param  array<int, int|string>  $keys
     * @param  array<int, string>  $noticeIds
     * @return array<string, true>
     */
    private function skipped(string $morphClass, array $keys, array $noticeIds): array
    {
        $skip = [];

        $delivered = NoticeDelivery::query()
            ->where('subject_type', $morphClass)
            ->whereIn('subject_id', $keys)
            ->whereIn('notice_id', $noticeIds)
            ->pluck('subject_id');

        $erased = SubjectProfile::query()
            ->where('subject_type', $morphClass)
            ->whereIn('subject_id', $keys)
            ->whereNotNull('erased_at')
            ->pluck('subject_id');

        foreach ($delivered->merge($erased) as $id) {
            $skip[(string) $id] = true;
        }

        return $skip;
    }
}
