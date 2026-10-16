<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Vipertecpro\DataRightsDesk\DataRightsDeskServiceProvider;
use Vipertecpro\DataRightsDesk\Enums\ConsentChannel;
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
