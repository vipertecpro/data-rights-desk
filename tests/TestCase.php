<?php

namespace Vipertecpro\DataRightsDesk\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase as Orchestra;
use Vipertecpro\DataRightsDesk\DataRightsDeskServiceProvider;
use Vipertecpro\DataRightsDesk\Facades\DataRights;
use Vipertecpro\DataRightsDesk\Tests\Support\User;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // §12 row 30: the package makes no outbound network calls.
        Http::preventStrayRequests();

        // Laravel's starter kits switch the app to immutable dates; run the suite both ways
        // (DATA_RIGHTS_IMMUTABLE_DATES=1 vendor/bin/pest) so neither kind of app breaks.
        if (getenv('DATA_RIGHTS_IMMUTABLE_DATES') === '1') {
            Date::use(CarbonImmutable::class);
        }
    }

    /** @return list<class-string> */
    protected function getPackageProviders($app): array
    {
        return [DataRightsDeskServiceProvider::class];
    }

    /** @return array<string, class-string> */
    protected function getPackageAliases($app): array
    {
        return ['DataRights' => DataRights::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        $app['config']->set('app.url', 'https://shop.example');
        $app['config']->set('database.default', env('DB_CONNECTION', 'testing'));
        $app['config']->set('cache.default', 'array');
        $app['config']->set('mail.default', 'array');
        $app['config']->set('queue.default', 'sync');

        $app['config']->set('data-rights.business.name', 'Example Shop');
        $app['config']->set('data-rights.business.website', 'https://shop.example');
        $app['config']->set('data-rights.contact.name', 'Asha Rao');
        $app['config']->set('data-rights.contact.role', 'Grievance officer');
        $app['config']->set('data-rights.contact.email', 'privacy@shop.example');
        $app['config']->set('data-rights.contact.phone', '+91 80 0000 0000');
        $app['config']->set('data-rights.subject_model', User::class);
        $app['config']->set('data-rights.checker.scan_paths', [__DIR__.'/fixtures/app']);
    }

    protected function defineRoutes($router): void
    {
        Route::get('/login', fn () => 'login')->name('login');
        Route::middleware(['web', 'auth', 'data-rights.activity'])->get('/home', fn () => 'home');
        Route::middleware(['web', 'auth', 'consent:marketing'])->get('/newsletter', fn () => 'newsletter');
        Route::middleware(['web', 'auth', 'data-rights.consent:analytics'])->get('/stats', fn () => 'stats');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /** @param  array<string, mixed>  $attributes */
    protected function makeUser(string $email = 'person@example.com', array $attributes = []): User
    {
        return User::query()->create([
            'name' => 'A Person',
            'email' => $email,
            ...$attributes,
        ]);
    }

    protected function allowDashboard(): void
    {
        Gate::define('viewDataRightsDesk', fn ($user) => $user->email === 'admin@shop.example');
    }

    protected function publishNotice(): int
    {
        return (int) DataRights::notices()->publish();
    }
}
