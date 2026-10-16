<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Vipertecpro\DataRightsDesk\Enums\RequestType;
use Vipertecpro\DataRightsDesk\Facades\DataRights;
use Vipertecpro\DataRightsDesk\Models\ConsentRecord;
use Vipertecpro\DataRightsDesk\Models\DataRightsRequest;
use Vipertecpro\DataRightsDesk\Models\Nomination;
use Vipertecpro\DataRightsDesk\Models\ProcessingLog;
use Vipertecpro\DataRightsDesk\Models\RequestEvent;
use Vipertecpro\DataRightsDesk\Models\RetentionRun;
use Vipertecpro\DataRightsDesk\Models\SubjectProfile;
use Vipertecpro\DataRightsDesk\Tests\Support\Order;

beforeEach(function () {
    $this->user = $this->makeUser();
});

function logAt(Model $user, int $daysAgo): void
{
    test()->travelTo(now()->subDays($daysAgo));
    DataRights::logProcessing($user, 'account', 'account.signed_in');
    test()->travelBack();
}

it('Rule 8(3): the keeper deletes processing logs older than keep_days', function () {
    logAt($this->user, 400);
    logAt($this->user, 10);

    $report = DataRights::retention()->run();

    expect($report->count('processing_logs'))->toBe(1)
        ->and(ProcessingLog::query()->count())->toBe(1);
});

it('Rule 8(3): keep_days below 365 is raised to 365', function () {
    config()->set('data-rights.retention.keep_days', 30);
    logAt($this->user, 100);
    logAt($this->user, 370);

    $report = DataRights::retention()->run();

    expect($report->keepDays)->toBe(365)
        ->and(ProcessingLog::query()->count())->toBe(1)
        ->and(DataRights::check()->hasWarning('keep_days_below_minimum'))->toBeTrue();
});

it('Rule 8(3): HasRetention models are erased after max(model days, 365), and only what retentionQuery allows', function () {
    config()->set('data-rights.retention.models', [Order::class]);
    Order::$days = 30;

    $make = function (string $status, int $daysAgo): Order {
        $this->travelTo(now()->subDays($daysAgo));
        $order = Order::query()->create(['status' => $status]);
        $this->travelBack();

        return $order;
    };

    $young = $make('closed', 200);
    $old = $make('closed', 400);
    $open = $make('open', 400);

    $report = DataRights::retention()->run();

    expect($report->models[Order::class])->toBe(1)
        ->and(Order::query()->pluck('id')->all())->toEqualCanonicalizing([$young->id, $open->id]);

    Order::$days = 500;
    $older = $make('closed', 450);
    DataRights::retention()->run();
    expect(Order::query()->whereKey($older->id)->exists())->toBeTrue();
    Order::$days = 30;
});

it('Rule 8(3): legal holds keep records from the keeper', function () {
    config()->set('data-rights.retention.models', [Order::class]);
    $this->travelTo(now()->subDays(400));
    $order = Order::query()->create(['status' => 'closed']);
    $this->travelBack();
    logAt($this->user, 400);

    DataRights::retention()->placeHold($order, 'Dispute with the courier');
    $hold = DataRights::retention()->placeHold($this->user, 'Police request', 'BNSS s.94');

    DataRights::retention()->run();

    expect(Order::query()->count())->toBe(1)
        ->and(ProcessingLog::query()->count())->toBe(1);

    DataRights::retention()->releaseHold($hold);
    DataRights::retention()->run();

    expect(ProcessingLog::query()->count())->toBe(0)
        ->and(Order::query()->count())->toBe(1);
});

it('Rule 8(3): records of people erased more than keep_days ago and old closed requests are deleted', function () {
    $this->publishNotice();
    DataRights::give($this->user, 'marketing');
    DataRights::nominations()->nominate($this->user, 'Meera', 'meera@example.com');
    $request = DataRights::requests()->open(RequestType::Grievance, $this->user, 'Too many emails', ['actor' => $this->user]);
    DataRights::requests()->complete($request, 'We stopped them.');

    $other = $this->makeUser('other@example.com');
    $otherRequest = DataRights::requests()->open(RequestType::Grievance, $other, null);
    DataRights::requests()->reject($otherRequest, 'Not ours.');

    SubjectProfile::for($this->user)->forceFill(['erased_at' => now()->subDays(10)])->save();
    expect(DataRights::retention()->run()->count('erased_people_records'))->toBe(0);

    $this->travel(400)->days();
    $report = DataRights::retention()->run();

    expect(ConsentRecord::query()->count())->toBe(0)
        ->and(Nomination::query()->count())->toBe(0)
        ->and(DataRightsRequest::query()->count())->toBe(0)
        ->and(RequestEvent::query()->count())->toBe(0)
        ->and(SubjectProfile::query()->whereMorphedTo('subject', $this->user)->exists())->toBeFalse()
        ->and($report->count('erased_people_records'))->toBeGreaterThan(0)
        ->and($report->count('closed_requests'))->toBe(1);
});

it('Rule 8(3): --pretend changes nothing and every run is recorded with counts', function () {
    logAt($this->user, 400);

    $this->artisan('data-rights:retain', ['--pretend' => true])
        ->expectsOutputToContain('Pretend: nothing was changed.')
        ->assertExitCode(0);

    expect(ProcessingLog::query()->count())->toBe(1);

    $this->artisan('data-rights:retain')->assertExitCode(0);

    expect(ProcessingLog::query()->count())->toBe(0)
        ->and(RetentionRun::query()->count())->toBe(2)
        ->and(RetentionRun::query()->where('pretend', true)->value('summary')['counts'])->toHaveKey('processing_logs')
        ->and(json_encode(DB::table('data_rights_retention_runs')->pluck('summary')))->not->toContain('person@example.com');
});
