<?php

use Illuminate\Support\Facades\Notification;
use Vipertecpro\DataRightsDesk\Enums\RequestType;
use Vipertecpro\DataRightsDesk\Facades\DataRights;
use Vipertecpro\DataRightsDesk\Models\Processor;
use Vipertecpro\DataRightsDesk\Notifications\RequestClosedNotification;
use Vipertecpro\DataRightsDesk\Tests\Support\UsersSource;

beforeEach(function () {
    config()->set('data-rights.sources', [UsersSource::class]);
    $this->publishNotice();
    $this->user = $this->makeUser('reader@example.com');
});

it('s.11: the access summary has exporter data, recent processing, consents, processors for the person\'s purposes and the contact', function () {
    DataRights::give($this->user, 'marketing');
    $this->travelTo(now()->subDays(400));
    DataRights::logProcessing($this->user, 'account', 'old.activity');
    $this->travelBack();
    DataRights::logProcessing($this->user, 'marketing', 'newsletter.sent');

    $shared = ['kind' => 'processor', 'data_shared' => ['Email address'], 'contract_reference' => 'C1', 'security_clause' => true];
    Processor::query()->create([...$shared, 'name' => 'Mail Sender', 'purposes' => ['marketing']]);
    Processor::query()->create([...$shared, 'name' => 'Stats Co', 'purposes' => ['analytics']]);
    Processor::query()->create([...$shared, 'name' => 'Police portal', 'kind' => 'fiduciary', 'purposes' => ['marketing'], 'hidden_from_access_summary' => true]);
    Processor::query()->create([...$shared, 'name' => 'Old vendor', 'purposes' => ['marketing'], 'active' => false]);

    $summary = DataRights::requests()->accessSummary($this->user);

    expect($summary['personal_data'][0])->toMatchArray([
        'source' => 'Account',
        'processing' => 'Your name and email, used to sign you in.',
        'records' => ['name' => 'A Person', 'email' => 'reader@example.com'],
    ])
        ->and(array_column($summary['processing_activities'], 'activity'))->toBe(['newsletter.sent'])
        ->and($summary['consents']['current']['marketing'])->toBeTrue()
        ->and($summary['consents']['history'])->toHaveCount(1)
        ->and(array_column($summary['shared_with'], 'name'))->toBe(['Mail Sender'])
        ->and($summary['contact']['email'])->toBe('privacy@shop.example');
});

it('s.11: the person downloads the summary only for a completed access request within download_days', function () {
    Notification::fake();
    $request = DataRights::requests()->open(RequestType::Access, $this->user, null, ['actor' => $this->user]);

    $this->actingAs($this->user)->get("/data-rights/requests/{$request->id}/download")->assertNotFound();

    DataRights::requests()->complete($request, 'Your summary is ready.');
    Notification::assertSentTo($this->user, RequestClosedNotification::class);

    $this->actingAs($this->user)->get("/data-rights/requests/{$request->id}/download")
        ->assertOk()
        ->assertHeader('Content-Disposition')
        ->assertJsonPath('personal_data.0.records.email', 'reader@example.com');

    $this->actingAs($this->user)->get("/data-rights/requests/{$request->id}")->assertOk()->assertSee('Download your data summary');

    $this->travel(31)->days();
    $this->actingAs($this->user)->get("/data-rights/requests/{$request->id}/download")->assertNotFound();
});
