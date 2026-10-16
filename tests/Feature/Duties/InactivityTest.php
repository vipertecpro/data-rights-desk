<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Vipertecpro\DataRightsDesk\Enums\ErasureReason;
use Vipertecpro\DataRightsDesk\Events\ErasureWarningSent;
use Vipertecpro\DataRightsDesk\Facades\DataRights;
use Vipertecpro\DataRightsDesk\Models\SubjectProfile;
use Vipertecpro\DataRightsDesk\Notifications\ErasureWarningNotification;
use Vipertecpro\DataRightsDesk\Tests\Support\UsersSource;

beforeEach(function () {
    Notification::fake();
    config()->set('data-rights.sources', [UsersSource::class]);
    UsersSource::$contexts = [];
    $this->travelTo(Carbon::parse('2025-01-01 09:00:00'));
    $this->user = $this->makeUser('quiet@example.com');
});

it('Rule 8(1): inactivity erasure is off by default', function () {
    $this->travelTo(Carbon::parse('2029-06-01 09:00:00'));

    $report = DataRights::retention()->run();

    expect($report->count('inactivity_warned'))->toBe(0)
        ->and($report->count('inactivity_erased'))->toBe(0);
    Notification::assertNothingSent();
});

it('Rule 8(1): the clock starts no earlier than counting_from', function () {
    config()->set('data-rights.retention.inactivity.enabled', true);

    // Created in January 2025, but the clock starts on 13 Nov 2025: the mark is 13 Nov 2028.
    $this->travelTo(Carbon::parse('2028-01-02 09:00:00'));
    expect(DataRights::retention()->run()->count('inactivity_warned'))->toBe(0);

    $this->travelTo(Carbon::parse('2028-11-10 09:00:00'));
    expect(DataRights::retention()->run()->count('inactivity_warned'))->toBe(0);
    Notification::assertNothingSent();
});

it('Rule 8(2): a warning goes out at least 48 hours before the three-year mark', function () {
    Event::fake([ErasureWarningSent::class]);
    config()->set('data-rights.retention.inactivity.enabled', true);
    $this->travelTo(Carbon::parse('2028-11-11 06:00:00'));

    expect(DataRights::retention()->run()->count('inactivity_warned'))->toBe(1)
        ->and(DataRights::retention()->run()->count('inactivity_warned'))->toBe(0);

    $profile = SubjectProfile::lookup($this->user);

    expect($profile->erasure_warned_at->toDateTimeString())->toBe('2028-11-11 06:00:00')
        ->and($profile->erasure_due_at->toDateTimeString())->toBe('2028-11-13 06:00:00');

    Notification::assertSentTo($this->user, ErasureWarningNotification::class, function (ErasureWarningNotification $n) {
        return $n->erasureDueAt->toDateTimeString() === '2028-11-13 06:00:00'
            && str_contains(implode(' ', $n->toMail($this->user)->introLines), 'sign in or contact us');
    });
    Event::assertDispatched(ErasureWarningSent::class);
});

it('Rule 8(2): signing in after the warning clears it', function () {
    config()->set('data-rights.retention.inactivity.enabled', true);
    $this->travelTo(Carbon::parse('2028-11-11 06:00:00'));
    DataRights::retention()->run();

    $this->travelTo(Carbon::parse('2028-11-12 06:00:00'));
    $this->actingAs($this->user)->get('/home')->assertOk();

    $profile = SubjectProfile::lookup($this->user);
    expect($profile->erasure_warned_at)->toBeNull()
        ->and($profile->last_seen_at->toDateTimeString())->toBe('2028-11-12 06:00:00');

    $this->travelTo(Carbon::parse('2028-11-14 06:00:00'));
    $report = DataRights::retention()->run();

    expect($report->count('inactivity_erased'))->toBe(0)
        ->and($report->count('inactivity_warned'))->toBe(0)
        ->and(UsersSource::$contexts)->toBe([]);
});

it('Rule 8(1): people who stay away are erased after the mark with the inactivity context', function () {
    config()->set('data-rights.retention.inactivity.enabled', true);
    $this->travelTo(Carbon::parse('2028-11-11 06:00:00'));
    DataRights::retention()->run();

    $this->travelTo(Carbon::parse('2028-11-12 07:00:00'));
    expect(DataRights::retention()->run()->count('inactivity_erased'))->toBe(0);

    $this->travelTo(Carbon::parse('2028-11-13 07:00:00'));
    expect(DataRights::retention()->run()->count('inactivity_erased'))->toBe(1);

    expect(UsersSource::$contexts)->toHaveCount(1)
        ->and(UsersSource::$contexts[0]->reason)->toBe(ErasureReason::Inactivity)
        ->and(UsersSource::$contexts[0]->keepsAccountAccess())->toBeTrue()
        ->and(SubjectProfile::lookup($this->user)->erased_at)->not->toBeNull();
});

it('Rule 8(1): a legal hold keeps a quiet person from inactivity erasure', function () {
    config()->set('data-rights.retention.inactivity.enabled', true);
    DataRights::retention()->placeHold($this->user, 'Open tax case');
    $this->travelTo(Carbon::parse('2028-11-11 06:00:00'));

    expect(DataRights::retention()->run()->count('inactivity_warned'))->toBe(0);
});

it('Rule 8(2): staff record contact from a person, which clears a pending warning', function () {
    config()->set('data-rights.retention.inactivity.enabled', true);
    $this->travelTo(Carbon::parse('2028-11-11 06:00:00'));
    DataRights::retention()->run();
    expect(SubjectProfile::lookup($this->user)->erasure_warned_at)->not->toBeNull();

    $this->allowDashboard();
    $admin = $this->makeUser('admin@shop.example');
    $this->travelTo(Carbon::parse('2028-11-12 09:00:00'));

    $this->actingAs($admin)->post('/data-rights-desk/retention/contact', ['email' => 'quiet@example.com'])
        ->assertRedirect('/data-rights-desk/retention')
        ->assertSessionHas('data-rights.status');

    $profile = SubjectProfile::lookup($this->user);
    expect($profile->erasure_warned_at)->toBeNull()
        ->and($profile->last_seen_at->toDateTimeString())->toBe('2028-11-12 09:00:00');

    $this->travelTo(Carbon::parse('2028-11-14 09:00:00'));
    expect(DataRights::retention()->run()->count('inactivity_erased'))->toBe(0);

    $this->actingAs($admin)->post('/data-rights-desk/retention/contact', ['email' => 'nobody@example.com'])->assertSessionHasErrors('contact_email');
});
