<?php

use Illuminate\Support\Facades\Notification;
use Vipertecpro\DataRightsDesk\Enums\RequestType;
use Vipertecpro\DataRightsDesk\Facades\DataRights;
use Vipertecpro\DataRightsDesk\Models\Processor;

function dashboardUrls(): array
{
    $person = test()->makeUser('person@example.com');
    test()->publishNotice();
    $request = DataRights::requests()->open(RequestType::Access, $person, 'Please send my data', ['actor' => $person]);
    $breach = DataRights::breaches()->record(['nature' => 'Lost backup']);
    $processor = Processor::query()->create(['name' => 'Mailer', 'kind' => 'processor', 'purposes' => ['marketing'], 'data_shared' => ['Email']]);
    $notice = DataRights::notices()->current('en');

    return [
        '/data-rights-desk', '/data-rights-desk/requests', '/data-rights-desk/requests?status=received&type=access&overdue=1',
        '/data-rights-desk/requests/create', "/data-rights-desk/requests/{$request->id}", "/data-rights-desk/requests/{$request->id}/summary",
        '/data-rights-desk/consents', '/data-rights-desk/consents?email=person@example.com', '/data-rights-desk/notices',
        "/data-rights-desk/notices/{$notice->id}", '/data-rights-desk/breaches', '/data-rights-desk/breaches/create',
        "/data-rights-desk/breaches/{$breach->id}", "/data-rights-desk/breaches/{$breach->id}/draft/alert",
        '/data-rights-desk/retention', '/data-rights-desk/processors', '/data-rights-desk/processors/create',
        "/data-rights-desk/processors/{$processor->id}/edit", '/data-rights-desk/children', '/data-rights-desk/check',
        '/data-rights-desk/api/summary', '/data-rights-desk/api/requests', "/data-rights-desk/api/requests/{$request->id}",
        '/data-rights-desk/api/consents', '/data-rights-desk/api/breaches', "/data-rights-desk/api/breaches/{$breach->id}",
        '/data-rights-desk/api/notices', '/data-rights-desk/api/retention', '/data-rights-desk/api/check',
    ];
}

beforeEach(function () {
    Notification::fake();
});

it('Security: guests are sent to sign in from the dashboard and its JSON', function () {
    $this->get('/data-rights-desk')->assertRedirect('/login');
    $this->get('/data-rights-desk/requests')->assertRedirect('/login');
    $this->getJson('/data-rights-desk/api/summary')->assertUnauthorized();
    $this->get('/data-rights/preferences')->assertRedirect('/login');
    $this->getJson('/data-rights/api/purposes')->assertUnauthorized();
});

it('Security: a signed-in user without the gate gets 403 on every dashboard page and JSON', function () {
    $urls = dashboardUrls();
    $someone = $this->makeUser('someone@example.com');

    foreach ($urls as $url) {
        $this->actingAs($someone)->get($url)->assertForbidden();
    }

    $this->actingAs($someone)->post('/data-rights-desk/notices/publish')->assertForbidden();
});

it('Security: the gate lets every dashboard page and JSON through', function () {
    $urls = dashboardUrls();
    $this->allowDashboard();
    $admin = $this->makeUser('admin@shop.example');

    foreach ($urls as $url) {
        $this->actingAs($admin)->get($url)->assertOk();
    }
});

it('Security: the default gate opens the dashboard only in local', function () {
    $user = $this->makeUser();

    $this->actingAs($user)->get('/data-rights-desk')->assertForbidden();

    $this->app['env'] = 'local';
    $this->actingAs($user)->get('/data-rights-desk')->assertOk();
});

it('Security: a person cannot see or change another person\'s request, download or nomination', function () {
    $owner = $this->makeUser('owner@example.com');
    $other = $this->makeUser('other@example.com');
    $request = DataRights::requests()->open(RequestType::Access, $owner, 'Mine', ['actor' => $owner]);
    DataRights::requests()->complete($request, 'Done');
    $nomination = DataRights::nominations()->nominate($owner, 'Meera', 'meera@example.com');

    $this->actingAs($other)->get("/data-rights/requests/{$request->id}")->assertForbidden();
    $this->actingAs($other)->get("/data-rights/requests/{$request->id}/download")->assertForbidden();
    $this->actingAs($other)->post("/data-rights/requests/{$request->id}/cancel")->assertForbidden();
    $this->actingAs($other)->delete("/data-rights/nominations/{$nomination->id}")->assertForbidden();
    $this->actingAs($other)->getJson("/data-rights/api/requests/{$request->id}")->assertForbidden();
    $this->actingAs($other)->get('/data-rights/requests')->assertDontSee('Meera');

    $this->actingAs($owner)->get("/data-rights/requests/{$request->id}/download")->assertOk();
    $this->actingAs($owner)->get('/data-rights/requests/not-a-ulid')->assertNotFound();
    expect($nomination->fresh())->not->toBeNull();
});

it('Security: request creation is throttled to six a minute', function () {
    $person = $this->makeUser();

    foreach (range(1, 6) as $i) {
        $this->actingAs($person)->post('/data-rights/requests', ['type' => 'grievance'])->assertRedirect();
    }

    $this->actingAs($person)->post('/data-rights/requests', ['type' => 'grievance'])->assertTooManyRequests();
});

it('Security: the asset route serves only the two stylesheets, with a long cache', function () {
    foreach (['data-rights.css', 'consent-fields.css'] as $file) {
        $this->get("/data-rights/assets/{$file}")->assertOk()
            ->assertHeader('Content-Type', 'text/css; charset=UTF-8')
            ->assertHeader('Cache-Control', 'immutable, max-age=31536000, public');
    }

    $this->get('/data-rights/assets/other.css')->assertNotFound();
    $this->get('/data-rights/assets/..%2Fcomposer.json')->assertNotFound();
});
