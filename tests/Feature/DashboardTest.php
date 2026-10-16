<?php

use Illuminate\Support\Facades\Notification;
use Vipertecpro\DataRightsDesk\Enums\RequestChannel;
use Vipertecpro\DataRightsDesk\Enums\RequestType;
use Vipertecpro\DataRightsDesk\Facades\DataRights;
use Vipertecpro\DataRightsDesk\Models\DataRightsRequest;
use Vipertecpro\DataRightsDesk\Models\LegalHold;

beforeEach(function () {
    Notification::fake();
    $this->allowDashboard();
    $this->admin = $this->makeUser('admin@shop.example');
    $this->person = $this->makeUser('person@example.com');
});

it('records a request received by email and keeps the received date', function () {
    $this->actingAs($this->admin)->post('/data-rights-desk/requests', ['email' => 'nobody@example.com', 'type' => 'access'])
        ->assertSessionHasErrors('email');

    $this->actingAs($this->admin)->post('/data-rights-desk/requests', [
        'email' => 'person@example.com', 'type' => 'grievance', 'details' => 'Phoned in', 'received_at' => now()->subDays(5)->format('Y-m-d'),
    ])->assertRedirect();

    $request = DataRightsRequest::query()->sole();
    expect($request->channel)->toBe(RequestChannel::Admin)
        ->and($request->identity_verified_at)->toBeNull()
        ->and($request->received_at->isSameDay(now()->subDays(5)))->toBeTrue();
});

it('publishes the notice from the dashboard', function () {
    $this->actingAs($this->admin)->get('/data-rights-desk/notices')->assertOk()->assertSee('Not published');
    $this->actingAs($this->admin)->post('/data-rights-desk/notices/publish')->assertSessionHas('data-rights.status', 'Published notice version 1.');
    $this->actingAs($this->admin)->post('/data-rights-desk/notices/publish')->assertSessionHas('data-rights.status', 'Nothing changed, so no new version was published.');
});

it('looks a person up by POST, so the email never appears in a URL', function () {
    $this->publishNotice();
    DataRights::give($this->person, 'marketing');

    $this->actingAs($this->admin)->post('/data-rights-desk/consents', ['email' => 'person@example.com'])
        ->assertOk()->assertSee('Download proof');
    $this->actingAs($this->admin)->get('/data-rights-desk/consents?email=person@example.com')
        ->assertOk()->assertDontSee('Download proof');
});

it('downloads consent proof and manages legal holds', function () {
    $this->publishNotice();
    DataRights::give($this->person, 'marketing');

    $this->actingAs($this->admin)->post('/data-rights-desk/consents/proof', ['email' => 'person@example.com', 'purpose' => 'marketing'])
        ->assertOk()->assertJsonCount(1, 'consents');
    $this->actingAs($this->admin)->post('/data-rights-desk/consents/proof', ['email' => 'person@example.com', 'purpose' => 'nope'])
        ->assertSessionHasErrors('purpose');

    $this->actingAs($this->admin)->post('/data-rights-desk/retention/holds', ['email' => 'person@example.com', 'reason' => 'Court order', 'law_reference' => 'CPC O.11'])
        ->assertRedirect('/data-rights-desk/retention');
    $hold = LegalHold::query()->sole();
    expect($hold->isActive())->toBeTrue();

    $this->actingAs($this->admin)->get('/data-rights-desk/retention')->assertOk()->assertSee('Court order')->assertSee('person@example.com');
    $this->actingAs($this->admin)->post("/data-rights-desk/retention/holds/{$hold->id}/release")->assertRedirect();
    expect($hold->fresh()->isActive())->toBeFalse();
});

it('shows the overview with the key number first', function () {
    DataRights::requests()->open(RequestType::Access, $this->person, null);

    $html = $this->actingAs($this->admin)->get('/data-rights-desk')->assertOk()->getContent();

    expect(strpos($html, 'Open requests'))->toBeLessThan(strpos($html, 'Overdue requests'))
        ->and($html)->toContain('drd-stat--key')
        ->and($html)->toContain('days left');
});
