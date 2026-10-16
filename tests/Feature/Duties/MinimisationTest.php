<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Vipertecpro\DataRightsDesk\Enums\RequestType;
use Vipertecpro\DataRightsDesk\Facades\DataRights;
use Vipertecpro\DataRightsDesk\Models\ConsentRecord;
use Vipertecpro\DataRightsDesk\Tests\Support\UsersSource;

it('Minimisation: IP address and user agent are not stored unless turned on', function () {
    $this->publishNotice();
    $user = $this->makeUser();
    $server = ['REMOTE_ADDR' => '203.0.113.9', 'HTTP_USER_AGENT' => 'TestBrowser/1.0'];

    $this->actingAs($user)->post('/data-rights/consents/marketing', [], $server);
    $first = ConsentRecord::query()->latest('occurred_at')->first();

    expect($first->ip_address)->toBeNull()->and($first->user_agent)->toBeNull();

    config()->set('data-rights.consent.record_ip', true);
    config()->set('data-rights.consent.record_user_agent', true);
    $this->actingAs($user)->delete('/data-rights/consents/marketing', [], $server);
    $second = DataRights::consents()->latest($user, 'marketing');

    expect($second->ip_address)->toBe('203.0.113.9')->and($second->user_agent)->toBe('TestBrowser/1.0');
});

it('Minimisation: package code writes no personal data to the log', function () {
    Notification::fake();
    Log::spy();
    config()->set('data-rights.sources', [UsersSource::class]);
    $this->publishNotice();
    $user = $this->makeUser('secret-person@example.com');

    DataRights::give($user, 'marketing');
    DataRights::withdraw($user, 'marketing');
    DataRights::logProcessing($user, 'marketing', 'newsletter.sent');
    $breach = DataRights::breaches()->record(['nature' => 'Lost laptop']);
    DataRights::breaches()->addAffected($breach, [$user]);
    DataRights::breaches()->notifyAffected($breach);
    DataRights::requests()->accessSummary($user);
    $request = DataRights::requests()->open(RequestType::Erasure, $user, 'Erase me', ['actor' => $user]);
    DataRights::requests()->erase($request);
    DataRights::retention()->run();
    DataRights::check();

    foreach (['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug', 'log', 'write'] as $level) {
        Log::shouldNotHaveReceived($level, fn (...$args) => str_contains(json_encode($args) ?: '', 'secret-person'));
    }

    expect(true)->toBeTrue();
});

it('Minimisation: only the age status is stored, never a birth date', function () {
    $user = $this->makeUser();

    $this->actingAs($user)->post('/data-rights/age', ['is_adult' => '0', 'date_of_birth' => '2012-03-04', 'age' => '14'])->assertRedirect();

    $row = (array) DB::table('data_rights_subjects')->first();

    expect($row['age_status'])->toBe('child')
        ->and(json_encode($row))->not->toContain('2012-03-04')->not->toContain('"14"')
        ->and(Schema::getColumnListing('data_rights_subjects'))->not->toContain('date_of_birth')->not->toContain('birth_date');
});
