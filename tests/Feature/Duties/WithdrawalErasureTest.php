<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Vipertecpro\DataRightsDesk\DataRightsManager;
use Vipertecpro\DataRightsDesk\Enums\ErasureReason;
use Vipertecpro\DataRightsDesk\Enums\RequestType;
use Vipertecpro\DataRightsDesk\Events\ConsentWithdrawn;
use Vipertecpro\DataRightsDesk\Events\PurposeDataErased;
use Vipertecpro\DataRightsDesk\Facades\DataRights;
use Vipertecpro\DataRightsDesk\Jobs\EraseWithdrawnPurpose;
use Vipertecpro\DataRightsDesk\Models\ProcessingLog;
use Vipertecpro\DataRightsDesk\Models\Processor;
use Vipertecpro\DataRightsDesk\Tests\Support\NewsletterSource;
use Vipertecpro\DataRightsDesk\Tests\Support\UsersSource;

// s.8(7): erase personal data when consent is withdrawn, and have the processors erase what they got.

beforeEach(function () {
    Notification::fake();
    Schema::create('drd_subscribers', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('list');
    });
    NewsletterSource::$contexts = [];
    config()->set('data-rights.sources', [UsersSource::class, NewsletterSource::class]);

    $this->publishNotice();
    $this->user = $this->makeUser();
    DB::table('drd_subscribers')->insert(['user_id' => $this->user->getKey(), 'list' => 'monthly']);

    $shared = ['kind' => 'processor', 'data_shared' => ['Email address'], 'contract_reference' => 'C1', 'security_clause' => true];
    Processor::query()->create([...$shared, 'name' => 'Mail Sender', 'purposes' => ['marketing']]);
    Processor::query()->create([...$shared, 'name' => 'Stats Co', 'purposes' => ['analytics']]);
    Processor::query()->create([...$shared, 'name' => 'Old Mailer', 'purposes' => ['marketing'], 'active' => false]);
});

afterEach(fn () => Schema::dropIfExists('drd_subscribers'));

it('s.8(7)(a): withdrawing consent runs the PurposeEraser for that purpose and logs the outcome', function () {
    Event::fake([PurposeDataErased::class]);
    DataRights::give($this->user, 'marketing');

    DataRights::withdraw($this->user, 'marketing');

    expect(DB::table('drd_subscribers')->count())->toBe(0)
        ->and(NewsletterSource::$contexts[0]->reason)->toBe(ErasureReason::Withdrawal)
        ->and(NewsletterSource::$contexts[0]->purpose)->toBe('marketing')
        ->and($this->user->fresh())->not->toBeNull()
        ->and(ProcessingLog::query()->sole()->activity)->toBe('Erased after consent was withdrawn: 1 erased, 0 kept');

    Event::assertDispatched(PurposeDataErased::class, fn (PurposeDataErased $event) => $event->purpose === 'marketing'
        && $event->report->erasedCount() === 1
        && array_column($event->report->processors, 'name') === ['Mail Sender']);
});

it('s.8(7)(a): a withdrawal of another purpose, or a consent given again before the job runs, erases nothing', function () {
    DataRights::give($this->user, 'analytics');
    DataRights::withdraw($this->user, 'analytics');
    expect(DB::table('drd_subscribers')->count())->toBe(1);

    Queue::fake();
    DataRights::give($this->user, 'marketing');
    DataRights::withdraw($this->user, 'marketing');
    DataRights::give($this->user, 'marketing');

    Queue::assertPushed(EraseWithdrawnPurpose::class, function (EraseWithdrawnPurpose $job) {
        $job->handle(app(DataRightsManager::class));

        return true;
    });
    expect(DB::table('drd_subscribers')->count())->toBe(1);
});

it('s.8(7)(a): a legal hold on the person keeps the data and the log says so', function () {
    DataRights::retention()->placeHold($this->user, 'Tax audit');
    DataRights::give($this->user, 'marketing');

    DataRights::withdraw($this->user, 'marketing');

    expect(DB::table('drd_subscribers')->count())->toBe(1)
        ->and(ProcessingLog::query()->sole()->activity)->toContain('legal hold');
});

it('s.6(6): the withdrawal event lists the active processors that got data for the purpose', function () {
    $processors = null;
    Event::listen(ConsentWithdrawn::class, function (ConsentWithdrawn $event) use (&$processors): void {
        $processors = $event->processors();
    });
    DataRights::give($this->user, 'marketing');

    DataRights::withdraw($this->user, 'marketing');

    expect($processors)->toBe([['name' => 'Mail Sender', 'kind' => 'processor', 'purposes' => ['marketing'], 'data_shared' => ['Email address']]]);
});

it('s.8(7)(b): an erasure request lists the processors to tell, and the dashboard shows them', function () {
    $this->allowDashboard();
    DataRights::give($this->user, 'marketing');
    $request = DataRights::requests()->open(RequestType::Erasure, $this->user, null, ['actor' => $this->user]);
    DataRights::requests()->verifyIdentity($request, 'Signed in');

    $report = DataRights::requests()->erase($request);

    expect(array_column($report->processors, 'name'))->toBe(['Mail Sender'])
        ->and($request->fresh()->result['processors_to_tell'][0]['name'])->toBe('Mail Sender');

    $this->actingAs($this->makeUser('admin@shop.example'))
        ->get("/data-rights-desk/requests/{$request->id}")
        ->assertOk()
        ->assertSee('Tell each of these processors to erase')
        ->assertSee('Mail Sender');
});
