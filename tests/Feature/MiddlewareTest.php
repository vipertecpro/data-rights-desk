<?php

use Vipertecpro\DataRightsDesk\Facades\DataRights;

beforeEach(function () {
    $this->publishNotice();
    $this->user = $this->makeUser();
});

it('lets a request through the consent middleware only when the purpose is allowed', function () {
    $this->get('/newsletter')->assertRedirect('/login');
    $this->getJson('/newsletter')->assertUnauthorized();

    $this->actingAs($this->user)->get('/newsletter')->assertRedirect('/data-rights/preferences');
    $this->actingAs($this->user)->getJson('/newsletter')->assertForbidden()->assertExactJson([
        'message' => 'Consent is needed for this purpose.',
        'purpose' => 'marketing',
        'preferences_url' => 'http://localhost/data-rights/preferences',
    ]);

    DataRights::give($this->user, 'marketing');
    $this->actingAs($this->user)->get('/newsletter')->assertOk()->assertSee('newsletter');

    $this->actingAs($this->user)->get('/stats')->assertRedirect('/data-rights/preferences');
});

it('records activity at most once a day', function () {
    $this->travelTo(now()->setTime(9, 0));
    $this->actingAs($this->user)->get('/home')->assertOk();
    $first = $this->user->dataRightsProfile()->first()->last_seen_at;

    $this->travelTo(now()->setTime(15, 0));
    $this->actingAs($this->user)->get('/home')->assertOk();

    expect($this->user->dataRightsProfile()->first()->last_seen_at->equalTo($first))->toBeTrue();

    $this->travel(1)->days();
    $this->actingAs($this->user)->get('/home')->assertOk();

    expect($this->user->dataRightsProfile()->first()->last_seen_at->greaterThan($first))->toBeTrue();
});
