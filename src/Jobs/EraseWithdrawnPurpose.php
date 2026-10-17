<?php

namespace Vipertecpro\DataRightsDesk\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Vipertecpro\DataRightsDesk\DataRightsManager;
use Vipertecpro\DataRightsDesk\Enums\ErasureReason;
use Vipertecpro\DataRightsDesk\Events\PurposeDataErased;
use Vipertecpro\DataRightsDesk\Models\Processor;
use Vipertecpro\DataRightsDesk\Support\ErasureContext;
use Vipertecpro\DataRightsDesk\Support\ErasureReport;
use Vipertecpro\DataRightsDesk\Support\Limits;
use Vipertecpro\DataRightsDesk\Support\Sources;

/**
 * s.8(7)(a): after a withdrawal, runs the PurposeEraser sources for that purpose and records
 * the outcome in the processing log, which the person sees in her access summary.
 */
class EraseWithdrawnPurpose implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** The person may be gone by the time the job runs: then there is nothing left to do. */
    public bool $deleteWhenMissingModels = true;

    public function __construct(
        public Model $subject,
        public string $purpose,
    ) {}

    public function handle(DataRightsManager $dataRights): void
    {
        // She may have given consent again before the job ran.
        if ($dataRights->allows($this->subject, $this->purpose)) {
            return;
        }

        $context = new ErasureContext(ErasureReason::Withdrawal, $this->subject, Limits::keepDays(), $this->purpose);
        $processors = Processor::toTell([$this->purpose]);

        if ($context->subjectIsHeld()) {
            $report = new ErasureReport(ErasureReason::Withdrawal, [], true, 0, $processors);
            $dataRights->logProcessing($this->subject, $this->purpose, 'Data kept after consent was withdrawn: legal hold');
        } else {
            $outcomes = [];

            foreach (Sources::purposeErasersFor($this->purpose) as $eraser) {
                $outcomes[$eraser::class] = $eraser->eraseForPurpose($this->subject, $this->purpose, $context);
            }

            $report = new ErasureReport(ErasureReason::Withdrawal, $outcomes, false, 0, $processors);
            $dataRights->logProcessing(
                $this->subject,
                $this->purpose,
                "Erased after consent was withdrawn: {$report->erasedCount()} erased, {$report->retainedCount()} kept",
            );
        }

        event(new PurposeDataErased($this->subject->getMorphClass(), $this->subject->getKey(), $this->purpose, $report));
    }
}
