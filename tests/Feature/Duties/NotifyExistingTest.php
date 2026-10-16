<?php

use Illuminate\Support\Facades\Notification;
use Vipertecpro\DataRightsDesk\Enums\DeliveryChannel;
use Vipertecpro\DataRightsDesk\Models\Notice;
use Vipertecpro\DataRightsDesk\Models\NoticeDelivery;
use Vipertecpro\DataRightsDesk\Models\SubjectProfile;
use Vipertecpro\DataRightsDesk\Notifications\NoticeNotification;

beforeEach(function () {
    Notification::fake();
    $this->travelTo(now()->setDate(2027, 3, 1));
    $this->first = $this->makeUser('first@example.com');
    $this->second = $this->makeUser('second@example.com');
    $this->travelTo(now()->setDate(2027, 6, 1));
    $this->late = $this->makeUser('late@example.com');
    $this->publishNotice();
});

it('s.5(2): notify-existing sends the notice once per person and records a legacy delivery', function () {
    $this->artisan('data-rights:notify-existing')->expectsOutputToContain('to 2 people')->assertExitCode(0);
    $this->artisan('data-rights:notify-existing')->expectsOutputToContain('to 0 people')->assertExitCode(0);

    Notification::assertSentToTimes($this->first, NoticeNotification::class, 1);
    Notification::assertSentToTimes($this->second, NoticeNotification::class, 1);
    Notification::assertNotSentTo($this->late, NoticeNotification::class);

    expect(NoticeDelivery::query()->count())->toBe(2)
        ->and(NoticeDelivery::query()->pluck('channel')->unique()->all())->toBe([DeliveryChannel::Legacy]);
});

it('s.5(2): notify-existing respects --before and --pretend', function () {
    $this->artisan('data-rights:notify-existing', ['--pretend' => true])->expectsOutputToContain('2 people would get')->assertExitCode(0);
    Notification::assertNothingSent();
    expect(NoticeDelivery::query()->count())->toBe(0);

    $this->artisan('data-rights:notify-existing', ['--before' => '2027-07-01', '--chunk' => 1])->expectsOutputToContain('to 3 people')->assertExitCode(0);
    Notification::assertSentToTimes($this->late, NoticeNotification::class, 1);
});

it('s.5(2): a later version goes out again and is recorded as an update', function () {
    $this->artisan('data-rights:notify-existing')->assertExitCode(0);

    config()->set('data-rights.purposes.marketing.title.en', 'Product news');
    $this->publishNotice();

    $this->artisan('data-rights:notify-existing')->expectsOutputToContain('version 2 to 2 people')->assertExitCode(0);

    Notification::assertSentToTimes($this->first, NoticeNotification::class, 2);
    expect(NoticeDelivery::query()->where('channel', DeliveryChannel::Update)->count())->toBe(2);
});

it('s.5(2): notify-existing needs a published notice', function () {
    Notice::query()->delete();

    $this->artisan('data-rights:notify-existing')->assertExitCode(1);
});

it('s.5(2): notify-existing skips people whose data was erased', function () {
    SubjectProfile::for($this->second)->forceFill(['erased_at' => now()])->save();

    $this->artisan('data-rights:notify-existing')->expectsOutputToContain('to 1 people')->assertExitCode(0);

    Notification::assertSentToTimes($this->first, NoticeNotification::class, 1);
    Notification::assertNotSentTo($this->second, NoticeNotification::class);
});
