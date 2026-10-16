<?php

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Vipertecpro\DataRightsDesk\DataRightsDeskServiceProvider;
use Vipertecpro\DataRightsDesk\Enums\RequestType;
use Vipertecpro\DataRightsDesk\Facades\DataRights;
use Vipertecpro\DataRightsDesk\Notifications\RequestReceivedNotification;
use Vipertecpro\DataRightsDesk\Support\Morphs;
use Vipertecpro\DataRightsDesk\Support\PackageRoutes;
use Vipertecpro\DataRightsDesk\Tests\Support\NoMailRouteUser;
use Vipertecpro\DataRightsDesk\Tests\Support\User;

// How the package behaves inside an existing app: other packages, the app's own routes and settings.

/** @return list<string> */
function checkerCodes(string $kind = 'errors'): array
{
    return array_column(DataRights::check()->{$kind}(), 'code');
}

function registerPackageRoutesAgain(): void
{
    $provider = app()->getProvider(DataRightsDeskServiceProvider::class);
    (fn () => $this->registerRoutes())->call($provider);
    app('router')->getRoutes()->refreshNameLookups();
    app('router')->getRoutes()->refreshActionLookups();
}

it('Integration: the route name list matches what the package registers', function () {
    $registered = collect(Route::getRoutes()->getRoutes())
        ->map(fn ($route) => $route->getName())
        ->filter(fn (?string $name) => $name !== null && preg_match('/^data-rights(-desk)?\./', $name) === 1)
        ->sort()->values()->all();

    expect($registered)->toBe(collect(PackageRoutes::expected())->sort()->values()->all());
});

it('Integration: the checker reports an app route that hides a package page', function () {
    expect(checkerCodes())->not->toContain('route_missing')->not->toContain('route_name_taken');

    // The app's routes load after the package's, so the app wins on the same address.
    Route::get('data-rights', fn () => 'the app\'s own page');
    app('router')->getRoutes()->refreshNameLookups();

    expect(checkerCodes())->toContain('route_missing');
});

it('Integration: the checker reports an app route that took a package route name', function () {
    Route::get('my-privacy', fn () => 'mine')->name('data-rights.notice');
    app('router')->getRoutes()->refreshNameLookups();

    $errors = DataRights::check()->errors();

    expect(array_column($errors, 'code'))->toContain('route_name_taken')
        ->and(collect($errors)->firstWhere('code', 'route_name_taken')['message'])->toContain('my-privacy');
});

it('Integration: the checker reports a missing login route, unless guests are redirected elsewhere', function () {
    $callback = new ReflectionProperty(Authenticate::class, 'redirectToCallback');
    $callback->setValue(null, null);
    $routes = app('router')->getRoutes();
    $kept = collect($routes->getRoutes())->reject(fn ($route) => $route->getName() === 'login');
    $fresh = new RouteCollection;
    $kept->each(fn ($route) => $fresh->add($route));
    app('router')->setRoutes($fresh);

    expect(checkerCodes())->toContain('login_route_missing');

    Authenticate::redirectUsing(fn () => '/admin/login');

    try {
        expect(checkerCodes())->not->toContain('login_route_missing');
    } finally {
        $callback->setValue(null, null);
    }
});

it('Integration: a local APP_URL is a warning, and an error in production', function () {
    config()->set('app.url', 'https://shop.example');
    expect(checkerCodes('warnings'))->not->toContain('app_url_local');

    config()->set('app.url', 'http://localhost:8000');
    expect(checkerCodes('warnings'))->toContain('app_url_local');

    app()->detectEnvironment(fn () => 'production');
    expect(checkerCodes())->toContain('app_url_local');
});

it('Integration: the checker names tables that common packages fill with personal data', function () {
    Schema::create('activity_log', function (Blueprint $table): void {
        $table->id();
        $table->nullableMorphs('causer');
        $table->json('properties')->nullable();
    });

    try {
        $warnings = DataRights::check()->warnings();

        expect(array_column($warnings, 'code'))->toContain('known_personal_table')
            ->and(collect($warnings)->firstWhere('code', 'known_personal_table')['message'])->toContain('activity_log');

        config()->set('data-rights.checker.ignore_tables', ['activity_log']);
        expect(checkerCodes('warnings'))->not->toContain('known_personal_table');
    } finally {
        Schema::dropIfExists('activity_log');
    }
});

it('Integration: the retention job and legal holds work in an app that enforces a morph map', function () {
    Relation::enforceMorphMap(['user' => User::class]);

    try {
        $user = $this->makeUser();
        $request = DataRights::requests()->open(RequestType::Grievance, $user, null);
        DataRights::retention()->placeHold($request, 'Court order 12/2027');

        $report = DataRights::retention()->run();

        expect($report)->not->toBeNull()
            ->and($request->getMorphClass())->toBe(get_class($request));
    } finally {
        Relation::requireMorphMap(false);
        Relation::morphMap([], false);
    }
});

it('Integration: emails go to the configured column when the model has no mail route', function () {
    Notification::fake();
    $user = NoMailRouteUser::query()->create(['name' => 'Phone first', 'email' => 'phone-first@example.com']);

    DataRights::requests()->open(RequestType::Grievance, $user, null);

    Notification::assertSentOnDemand(
        RequestReceivedNotification::class,
        fn ($notification, array $channels, object $notifiable) => $notifiable->routes['mail'] === 'phone-first@example.com',
    );
});

it('Integration: the public pages can use another guard', function () {
    config()->set('auth.guards.customer', ['driver' => 'session', 'provider' => 'users']);
    config()->set('data-rights.routes.public.auth', 'auth:customer');
    registerPackageRoutesAgain();

    expect(Route::getRoutes()->getByName('data-rights.preferences')->gatherMiddleware())->toContain('auth:customer');

    $this->actingAs($this->makeUser(), 'customer')->get('/data-rights/preferences')->assertOk();
});

it('Integration: an array of sign-in middleware for the JSON feed does not break route registration', function () {
    config()->set('data-rights.routes.api.auth', ['auth', 'verified']);
    registerPackageRoutesAgain();

    expect(Route::getRoutes()->getByName('data-rights.api.purposes')->gatherMiddleware())->toContain('auth', 'verified');
});

it('Integration: morph_key_type sets the id type of the columns that point at a person', function (string $type) {
    config()->set('data-rights.morph_key_type', $type);

    Schema::create('drd_morph_probe', function (Blueprint $table): void {
        $table->id();
        Morphs::add($table, 'subject');
        Morphs::add($table, 'actor', nullable: true);
    });

    try {
        $columns = collect(Schema::getColumns('drd_morph_probe'))->keyBy('name');

        expect(strtolower($columns['subject_id']['type_name']))->not->toContain('int')
            ->and($columns['subject_id']['nullable'])->toBeFalse()
            ->and($columns['actor_id']['nullable'])->toBeTrue();
    } finally {
        Schema::dropIfExists('drd_morph_probe');
    }
})->with(['uuid', 'ulid']);

it('Integration: running install again does not copy the migrations twice', function () {
    try {
        $this->artisan('data-rights:install')->assertExitCode(0);
        $this->travel(1)->days();
        $this->artisan('data-rights:install')
            ->expectsOutputToContain('already in database/migrations')
            ->assertExitCode(0);

        expect(File::glob(database_path('migrations/*create_data_rights_consents_table.php')))->toHaveCount(1);
    } finally {
        File::delete(config_path('data-rights.php'));
        File::delete(File::glob(database_path('migrations/*data_rights*')));
    }
});
