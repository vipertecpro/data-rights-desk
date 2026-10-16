<?php

use Illuminate\Support\Facades\Notification;
use Vipertecpro\DataRightsDesk\Models\Notice;

beforeEach(function () {
    Notification::fake();
    $this->user = $this->makeUser();
});

it('serves the notice as JSON without sign-in', function () {
    $this->getJson('/data-rights/api/notice')->assertNotFound();

    $this->publishNotice();

    $this->getJson('/data-rights/api/notice?lang=hi')->assertOk()
        ->assertJsonPath('version', 1)
        ->assertJsonPath('locale', 'hi')
        ->assertJsonPath('hash', Notice::query()->where('locale', 'hi')->value('hash'))
        ->assertJsonPath('languages.1.name', 'हिन्दी');
});

it('lists purposes with the person\'s state and records consent as JSON', function () {
    $this->actingAs($this->user)->postJson('/data-rights/api/consents/marketing')->assertStatus(409);

    $this->publishNotice();

    $this->actingAs($this->user)->getJson('/data-rights/api/purposes')->assertOk()
        ->assertJsonPath('notice.version', 1)
        ->assertJsonPath('purposes.1.key', 'marketing')
        ->assertJsonPath('purposes.1.allowed', false)
        ->assertJsonPath('children.may_track', true);

    $this->actingAs($this->user)->postJson('/data-rights/api/consents/marketing')->assertCreated()
        ->assertJsonPath('action', 'given')
        ->assertJsonPath('allowed', true)
        ->assertJsonPath('notice_version', 1);

    $this->actingAs($this->user)->deleteJson('/data-rights/api/consents/marketing')->assertOk()
        ->assertJsonPath('action', 'withdrawn')
        ->assertJsonPath('allowed', false);

    $this->actingAs($this->user)->postJson('/data-rights/api/consents/unknown')->assertNotFound();
});

it('opens and lists the person\'s requests as JSON', function () {
    $this->actingAs($this->user)->postJson('/data-rights/api/requests', ['type' => 'nonsense'])->assertUnprocessable();

    $id = $this->actingAs($this->user)->postJson('/data-rights/api/requests', ['type' => 'access', 'details' => 'All of it'])
        ->assertCreated()
        ->assertJsonPath('data.type', 'access')
        ->assertJsonPath('data.status', 'received')
        ->json('data.id');

    $this->actingAs($this->user)->getJson('/data-rights/api/requests')->assertOk()->assertJsonCount(1, 'data');
    $this->actingAs($this->user)->getJson("/data-rights/api/requests/{$id}")->assertOk()->assertJsonPath('data.details', 'All of it');
});
