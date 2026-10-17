<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Vipertecpro\DataRightsDesk\DataRightsDeskServiceProvider;
use Vipertecpro\DataRightsDesk\Enums\ConsentChannel;
use Vipertecpro\DataRightsDesk\Enums\RequestType;
use Vipertecpro\DataRightsDesk\Facades\DataRights;
use Vipertecpro\DataRightsDesk\Models\ConsentRecord;
use Vipertecpro\DataRightsDesk\Tests\Support\User;

// A mobile or NativePHP app signs in with a token, not a browser session.
// routes.api.middleware ['api'] and routes.api.auth 'auth:<guard>' make the JSON feed work for it.
function useTokenApi(object $test, ?string $guard = 'drd-token'): void
{
    config()->set('auth.guards.drd-token', ['driver' => 'drd-token']);
    Auth::viaRequest('drd-token', fn (Request $request) => $request->bearerToken() === 'phone-token'
        ? User::query()->where('email', 'person@example.com')->first()
        : null);

    config()->set('data-rights.routes.api.middleware', ['api']);
    config()->set('data-rights.routes.api.auth', 'auth:'.$guard);

    // Routes read the config when they are registered, so register them again.
    $provider = app()->getProvider(DataRightsDeskServiceProvider::class);
    (fn () => $this->registerRoutes())->call($provider);
    app('router')->getRoutes()->refreshNameLookups();
    app('router')->getRoutes()->refreshActionLookups();
}

beforeEach(function () {
    $this->person = $this->makeUser('person@example.com');
    $this->publishNotice();
    useTokenApi($this);
});

it('lets a token client read purposes and give and withdraw consent without a session', function () {
    $this->withToken('phone-token')->getJson('/data-rights/api/purposes')
        ->assertOk()
        ->assertJsonPath('purposes.1.key', 'marketing')
        ->assertJsonPath('purposes.1.allowed', false);

    $this->withToken('phone-token')->postJson('/data-rights/api/consents/marketing')->assertSuccessful();
    expect(DataRights::allows($this->person, 'marketing'))->toBeTrue()
        ->and(ConsentRecord::query()->latest('occurred_at')->first()->channel)->toBe(ConsentChannel::Api);

    $this->withToken('phone-token')->deleteJson('/data-rights/api/consents/marketing')->assertSuccessful();
    expect(DataRights::allows($this->person, 'marketing'))->toBeFalse();
});

it('lets a token client open and read its own requests', function () {
    $id = $this->withToken('phone-token')->postJson('/data-rights/api/requests', ['type' => 'access'])
        ->assertCreated()
        ->json('data.id');

    $this->withToken('phone-token')->getJson('/data-rights/api/requests')->assertOk()->assertJsonCount(1, 'data');
    $this->withToken('phone-token')->getJson("/data-rights/api/requests/{$id}")->assertOk()->assertJsonPath('data.type', 'access');
});

it('refuses a token client with no token or a wrong one, and keeps the notice public', function () {
    $this->getJson('/data-rights/api/purposes')->assertUnauthorized();
    $this->withToken('wrong')->postJson('/data-rights/api/consents/marketing')->assertUnauthorized();
    $this->getJson('/data-rights/api/notice')->assertOk();
});

it('gives a sign-up screen the notice id and the consent purposes without sign-in', function () {
    $notice = DataRights::notices()->current('en');

    $this->getJson('/data-rights/api/notice')->assertOk()
        ->assertJsonPath('id', $notice->getKey())
        ->assertJsonPath('consent_purposes.0.key', 'account')
        ->assertJsonPath('consent_purposes.1.key', 'marketing');
});

it('lets a token client download its completed access summary, even with the public pages off', function () {
    config()->set('data-rights.routes.public.enabled', false);
    useTokenApi($this);

    $request = DataRights::requests()->open(RequestType::Access, $this->person, null, ['actor' => $this->person]);
    DataRights::requests()->complete($request, 'Your summary is ready.');

    $url = $this->withToken('phone-token')->getJson("/data-rights/api/requests/{$request->id}")
        ->assertOk()
        ->json('data.download_url');

    expect($url)->toEndWith("/data-rights/api/requests/{$request->id}/download");

    $this->withToken('phone-token')->getJson("/data-rights/api/requests/{$request->id}/download")
        ->assertOk()
        ->assertJsonPath('contact.email', 'privacy@shop.example');
});

it('limits consent changes to 30 a minute for each person', function () {
    foreach (range(1, 30) as $i) {
        $this->withToken('phone-token')->postJson('/data-rights/api/consents/marketing')->assertSuccessful();
    }

    $this->withToken('phone-token')->postJson('/data-rights/api/consents/marketing')->assertStatus(429);
});
