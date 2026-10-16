<?php

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Vipertecpro\DataRightsDesk\Enums\ErasureReason;
use Vipertecpro\DataRightsDesk\Enums\RequestStatus;
use Vipertecpro\DataRightsDesk\Enums\RequestType;
use Vipertecpro\DataRightsDesk\Events\PersonalDataErased;
use Vipertecpro\DataRightsDesk\Exceptions\IdentityNotVerified;
use Vipertecpro\DataRightsDesk\Facades\DataRights;
use Vipertecpro\DataRightsDesk\Models\Nomination;
use Vipertecpro\DataRightsDesk\Models\SubjectProfile;
use Vipertecpro\DataRightsDesk\Notifications\RequestClosedNotification;
use Vipertecpro\DataRightsDesk\Tests\Support\InvoicesSource;
use Vipertecpro\DataRightsDesk\Tests\Support\User;
use Vipertecpro\DataRightsDesk\Tests\Support\UsersSource;

beforeEach(function () {
    config()->set('data-rights.sources', [UsersSource::class, InvoicesSource::class]);
    UsersSource::$contexts = [];
    Notification::fake();
    $this->user = $this->makeUser('leaving@example.com');
});

it('s.12(3): an erasure request runs every Eraser, stores the report, tells the person at the captured address and marks her erased', function () {
    Event::fake([PersonalDataErased::class]);
    DataRights::nominations()->nominate($this->user, 'Meera', 'meera@example.com');
    $request = DataRights::requests()->open(RequestType::Erasure, $this->user, 'Please erase my account', ['actor' => $this->user]);

    expect($request->identity_method)->toBe('signed_in');

    $report = DataRights::requests()->erase($request);
    $request->refresh();

    expect($report->held)->toBeFalse()
        ->and($report->erasedCount())->toBe(2)
        ->and($report->retainedCount())->toBe(2)
        ->and(User::query()->where('email', 'leaving@example.com')->exists())->toBeFalse()
        ->and(UsersSource::$contexts[0]->reason)->toBe(ErasureReason::Request)
        ->and($request->status)->toBe(RequestStatus::Completed)
        ->and($request->result['sources']['Account']['erased'])->toBe(1)
        ->and($request->result['sources']['Invoices']['retained'][0]['reason'])->toBe('Tax invoices are kept for 8 years under the GST Act.')
        ->and($request->response)->toContain('We erased your personal data.')->toContain('GST Act')
        ->and(Nomination::query()->count())->toBe(0)
        ->and(SubjectProfile::query()->where('subject_id', $this->user->id)->value('erased_at'))->not->toBeNull()
        ->and(json_encode($request->result))->not->toContain('leaving@example.com');

    Notification::assertSentOnDemand(
        RequestClosedNotification::class,
        fn ($notification, array $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'leaving@example.com'
            && $notification->request->is($request),
    );
    Event::assertDispatched(PersonalDataErased::class, fn ($e) => $e->subjectId === $this->user->id && $e->report->reason === ErasureReason::Request);
});

it('s.12(3): a legal hold on the person stops erasure and the report says so', function () {
    DataRights::retention()->placeHold($this->user, 'Court order', 'CrPC s.91');
    $request = DataRights::requests()->open(RequestType::Erasure, $this->user, null, ['actor' => $this->user]);

    $report = DataRights::requests()->erase($request);
    $request->refresh();

    expect($report->held)->toBeTrue()
        ->and($request->result['held'])->toBeTrue()
        ->and($request->status)->toBe(RequestStatus::InProgress)
        ->and(UsersSource::$contexts)->toBe([])
        ->and(User::query()->whereKey($this->user->id)->exists())->toBeTrue();

    Notification::assertNotSentTo($this->user, RequestClosedNotification::class);
});

it('s.12(3): erasure waits for an identity check, and the dashboard needs a confirm box', function () {
    $request = DataRights::requests()->open(RequestType::Erasure, $this->user, null, ['channel' => 'admin']);

    expect($request->identity_verified_at)->toBeNull()
        ->and(fn () => DataRights::requests()->erase($request))->toThrow(IdentityNotVerified::class);

    $this->allowDashboard();
    $admin = $this->makeUser('admin@shop.example');

    $this->actingAs($admin)->post("/data-rights-desk/requests/{$request->id}/erase")->assertSessionHasErrors('confirm');
    $this->actingAs($admin)->post("/data-rights-desk/requests/{$request->id}/verify", ['method' => 'Reply from the registered email'])->assertRedirect();
    $this->actingAs($admin)->post("/data-rights-desk/requests/{$request->id}/erase", ['confirm' => '1'])
        ->assertRedirect("/data-rights-desk/requests/{$request->id}")
        ->assertSessionHas('data-rights.status');

    expect($request->fresh()->status)->toBe(RequestStatus::Completed)
        ->and($request->fresh()->identity_method)->toBe('Reply from the registered email');
});

it('s.12(3): deleting the user model marks the profile erased', function () {
    $this->user->delete();

    expect(SubjectProfile::query()->where('subject_id', $this->user->id)->value('erased_at'))->not->toBeNull();
});
