<?php

use Illuminate\Support\Carbon;
use Vipertecpro\DataRightsDesk\Enums\ErasureReason;
use Vipertecpro\DataRightsDesk\Enums\LanguageStatus;
use Vipertecpro\DataRightsDesk\Support\Dates;
use Vipertecpro\DataRightsDesk\Support\ErasureContext;
use Vipertecpro\DataRightsDesk\Support\ErasureOutcome;
use Vipertecpro\DataRightsDesk\Support\ErasureReport;
use Vipertecpro\DataRightsDesk\Support\Languages;
use Vipertecpro\DataRightsDesk\Support\Limits;

it('combines erasure outcomes into a report of counts and reasons', function () {
    $outcome = ErasureOutcome::erased(3)->and(ErasureOutcome::retained(2, 'GST Act'))->and(ErasureOutcome::nothing());
    $report = new ErasureReport(ErasureReason::Request, ['Orders' => $outcome], false, 1);

    expect($outcome->erasedCount)->toBe(3)
        ->and($outcome->retainedCount())->toBe(2)
        ->and($report->toArray())->toMatchArray(['reason' => 'request', 'held' => false, 'erased' => 3, 'retained' => 2, 'nominations_deleted' => 1])
        ->and($report->retainedReasons())->toBe(['GST Act'])
        ->and(ErasureReport::blockedByHold(ErasureReason::Inactivity)->held)->toBeTrue();
});

it('keeps processing records for at least keep_days from the processing', function () {
    config()->set('data-rights.retention.keep_days', 10);
    $context = new ErasureContext(ErasureReason::Request, $this->makeUser(), Limits::keepDays());

    expect($context->keepUntil(Carbon::parse('2027-01-01'))->toDateString())->toBe('2028-01-01')
        ->and($context->keepsAccountAccess())->toBeFalse()
        ->and($context->subjectIsHeld())->toBeFalse();
});

it('reads language status from meta.php and treats a missing one as needed', function () {
    expect(Languages::status('en'))->toBe(LanguageStatus::Ready)
        ->and(Languages::status('hi'))->toBe(LanguageStatus::NeedsReview)
        ->and(Languages::status('fr'))->toBe(LanguageStatus::Needed)
        ->and(Languages::nativeName('hi'))->toBe('हिन्दी')
        ->and(Languages::resolve('fr', 'hi'))->toBe('hi')
        ->and(Languages::resolve(null, 'xx'))->toBe('en');
});

it('writes due dates as people read them', function () {
    $this->travelTo(Carbon::parse('2026-12-04 10:00:00'));

    expect(Dates::due(Carbon::parse('2027-01-14 10:00:00')))->toBe('Due 14 Jan 2027 (41 days left)')
        ->and(Dates::due(Carbon::parse('2026-12-01 10:00:00')))->toBe('Due 1 Dec 2026 (3 days overdue)')
        ->and(Dates::hours(Carbon::parse('2026-12-04 16:00:00')))->toBe('6 h left');
});
