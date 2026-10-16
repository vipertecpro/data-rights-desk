<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Vipertecpro\DataRightsDesk\Enums\RequestStatus;
use Vipertecpro\DataRightsDesk\Events\RequestReceived;
use Vipertecpro\DataRightsDesk\Events\RequestStatusChanged;
use Vipertecpro\DataRightsDesk\Exceptions\InvalidTransition;
use Vipertecpro\DataRightsDesk\Facades\DataRights;
use Vipertecpro\DataRightsDesk\Models\DataRightsRequest;
use Vipertecpro\DataRightsDesk\Notifications\RequestClosedNotification;
use Vipertecpro\DataRightsDesk\Notifications\RequestReceivedNotification;

it('s.12(2): correction, completion and updating requests move through statuses with events and a reply', function (string $type) {
    Notification::fake();
    Event::fake([RequestReceived::class, RequestStatusChanged::class]);
    $this->allowDashboard();
    $person = $this->makeUser();
    $admin = $this->makeUser('admin@shop.example');

    $this->actingAs($person)->post('/data-rights/requests', ['type' => $type, 'details' => 'My surname is Rao, not Roa.'])->assertRedirect();
    $request = DataRightsRequest::query()->sole();

    expect($request->status)->toBe(RequestStatus::Received)
        ->and($request->identity_method)->toBe('signed_in');
    Event::assertDispatched(RequestReceived::class);
    Notification::assertSentTo($person, RequestReceivedNotification::class);

    $this->actingAs($admin)->post("/data-rights-desk/requests/{$request->id}/status", ['status' => 'in_progress', 'note' => 'Checking the order records'])->assertRedirect();
    $this->actingAs($admin)->post("/data-rights-desk/requests/{$request->id}/complete", ['response' => 'We corrected your surname.'])->assertRedirect();

    $request->refresh();
    expect($request->status)->toBe(RequestStatus::Completed)
        ->and($request->response)->toBe('We corrected your surname.')
        ->and($request->closed_at)->not->toBeNull()
        ->and($request->events()->count())->toBe(4)
        ->and($request->events()->get()->last()->actor_id)->toBe($admin->id);

    Event::assertDispatchedTimes(RequestStatusChanged::class, 2);
    Notification::assertSentTo($person, RequestClosedNotification::class);

    $this->actingAs($person)->get("/data-rights/requests/{$request->id}")->assertOk()->assertSee('We corrected your surname.')->assertDontSee('Checking the order records');

    expect(fn () => DataRights::requests()->transition($request, RequestStatus::InProgress))->toThrow(InvalidTransition::class);
})->with(['correction', 'completion', 'updating']);

it('s.12(2): a person can cancel her own open request', function () {
    $person = $this->makeUser();
    $this->actingAs($person)->post('/data-rights/requests', ['type' => 'updating'])->assertRedirect();
    $request = DataRightsRequest::query()->sole();

    $this->actingAs($person)->post("/data-rights/requests/{$request->id}/cancel")->assertRedirect();

    expect($request->fresh()->status)->toBe(RequestStatus::Cancelled);
});
