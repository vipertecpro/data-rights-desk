<?php

use Illuminate\Support\Carbon;
use Vipertecpro\DataRightsDesk\Enums\RequestType;
use Vipertecpro\DataRightsDesk\Facades\DataRights;

it('Rule 14(3): a grievance is due within 90 days', function () {
    $this->travelTo(Carbon::parse('2027-06-01 10:00:00'));

    $request = DataRights::requests()->open(RequestType::Grievance, $this->makeUser(), 'You keep emailing me');

    expect($request->due_at->toDateTimeString())->toBe('2027-08-30 10:00:00')
        ->and((int) $request->received_at->diffInDays($request->due_at))->toBe(90);
});

it('Rule 14(3): response_days above 90 is clamped to 90 and flagged', function () {
    config()->set('data-rights.requests.response_days', 120);

    $request = DataRights::requests()->open(RequestType::Access, $this->makeUser(), null);

    expect((int) $request->received_at->diffInDays($request->due_at))->toBe(90)
        ->and(DataRights::check()->hasError('response_days_over_90'))->toBeTrue();

    config()->set('data-rights.requests.response_days', 30);
    $shorter = DataRights::requests()->open(RequestType::Access, $this->makeUser('two@example.com'), null);
    expect((int) $shorter->received_at->diffInDays($shorter->due_at))->toBe(30);
});

it('Rule 14(3): overdue requests are flagged on the dashboard and by the checker', function () {
    $this->travelTo(Carbon::parse('2027-06-01 10:00:00'));
    $request = DataRights::requests()->open(RequestType::Grievance, $this->makeUser(), null);

    expect(DataRights::check()->hasError('requests_overdue'))->toBeFalse();

    $this->travelTo(Carbon::parse('2027-09-02 10:00:00'));

    expect($request->fresh()->isOverdue())->toBeTrue()
        ->and(DataRights::check()->hasError('requests_overdue'))->toBeTrue();

    $this->allowDashboard();
    $admin = $this->makeUser('admin@shop.example');

    $this->actingAs($admin)->get('/data-rights-desk')->assertOk()->assertSee('3 days overdue');
    $this->actingAs($admin)->get('/data-rights-desk/requests?overdue=1')->assertOk()->assertSee('Grievance')->assertSee('drd-danger', false);
    $this->actingAs($admin)->getJson('/data-rights-desk/api/requests')->assertOk()->assertJsonPath('data.0.overdue', true);
});
