<?php

namespace Vipertecpro\DataRightsDesk;

use Closure;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Vipertecpro\DataRightsDesk\Breaches\BreachDesk;
use Vipertecpro\DataRightsDesk\Checker\Checker;
use Vipertecpro\DataRightsDesk\Children\ChildGuard;
use Vipertecpro\DataRightsDesk\Consents\ConsentLedger;
use Vipertecpro\DataRightsDesk\Contracts\IdentityVerifier;
use Vipertecpro\DataRightsDesk\Contracts\ParentalConsentVerifier;
use Vipertecpro\DataRightsDesk\Http\Controllers\AssetController;
use Vipertecpro\DataRightsDesk\Http\Middleware\RecordActivity;
use Vipertecpro\DataRightsDesk\Http\Middleware\RequireConsent;
use Vipertecpro\DataRightsDesk\Nominations\NominationBook;
use Vipertecpro\DataRightsDesk\Notices\NoticeBuilder;
use Vipertecpro\DataRightsDesk\Notices\NoticeRepository;
use Vipertecpro\DataRightsDesk\Purposes\PurposeRegistry;
use Vipertecpro\DataRightsDesk\Requests\RequestDesk;
use Vipertecpro\DataRightsDesk\Retention\RetentionKeeper;
use Vipertecpro\DataRightsDesk\Verification\NoParentalConsentVerifier;
use Vipertecpro\DataRightsDesk\Verification\SignedInSubjectVerifier;
use Vipertecpro\DataRightsDesk\View\LayoutComposer;

class DataRightsDeskServiceProvider extends ServiceProvider
{
    public const GATE = 'viewDataRightsDesk';

    private static ?Closure $defaultGate = null;

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/data-rights.php', 'data-rights');

        foreach ([
            PurposeRegistry::class,
            NoticeBuilder::class,
            NoticeRepository::class,
            ChildGuard::class,
            ConsentLedger::class,
            RequestDesk::class,
            NominationBook::class,
            BreachDesk::class,
            RetentionKeeper::class,
            Checker::class,
        ] as $service) {
            $this->app->singleton($service);
        }

        $this->app->singleton(DataRightsManager::class);
        $this->app->alias(DataRightsManager::class, 'data-rights');

        $this->app->bindIf(IdentityVerifier::class, SignedInSubjectVerifier::class);
        $this->app->bindIf(ParentalConsentVerifier::class, NoParentalConsentVerifier::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'data-rights');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'data-rights');

        View::composer(['data-rights::layouts.public', 'data-rights::layouts.desk'], LayoutComposer::class);

        $this->registerPublishing();
        $this->registerCommands();
        $this->registerGate();
        $this->registerMiddleware();
        $this->registerBladeDirectives();
        $this->registerRoutes();
    }

    /** True while the viewDataRightsDesk gate is the package default (local only). */
    public static function usesDefaultGate(): bool
    {
        return self::$defaultGate !== null && (Gate::abilities()[self::GATE] ?? null) === self::$defaultGate;
    }

    private function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([__DIR__.'/../config/data-rights.php' => config_path('data-rights.php')], 'data-rights-config');
        $this->publishesMigrations([__DIR__.'/../database/migrations' => database_path('migrations')], 'data-rights-migrations');
        $this->publishes([__DIR__.'/../resources/views' => resource_path('views/vendor/data-rights')], 'data-rights-views');
        $this->publishes([__DIR__.'/../lang' => $this->app->langPath('vendor/data-rights')], 'data-rights-lang');
    }

    private function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            Console\InstallCommand::class,
            Console\NoticeCommand::class,
            Console\NotifyExistingCommand::class,
            Console\RetainCommand::class,
            Console\ProofCommand::class,
            Console\BreachDraftCommand::class,
            Console\CheckCommand::class,
            Console\MakeSourceCommand::class,
        ]);
    }

    /** Like Horizon: open in local, closed elsewhere, until the app defines its own gate. */
    private function registerGate(): void
    {
        if (Gate::has(self::GATE)) {
            return;
        }

        self::$defaultGate = static fn (mixed $user = null): bool => app()->environment('local');

        Gate::define(self::GATE, self::$defaultGate);
    }

    private function registerMiddleware(): void
    {
        /** @var Router $router */
        $router = $this->app->make('router');

        $router->aliasMiddleware('data-rights.consent', RequireConsent::class);
        $router->aliasMiddleware('data-rights.activity', RecordActivity::class);

        if (! array_key_exists('consent', $router->getMiddleware())) {
            $router->aliasMiddleware('consent', RequireConsent::class);
        }
    }

    private function registerBladeDirectives(): void
    {
        Blade::directive('dataRightsAllows', fn (string $expression): string => "<?php if (\\Vipertecpro\\DataRightsDesk\\View\\Directives::allows({$expression})): ?>");
        Blade::directive('endDataRightsAllows', fn (): string => '<?php endif; ?>');
        Blade::directive('dataRightsTracking', fn (): string => '<?php if (\\Vipertecpro\\DataRightsDesk\\View\\Directives::tracking()): ?>');
        Blade::directive('endDataRightsTracking', fn (): string => '<?php endif; ?>');
    }

    private function registerRoutes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        $public = (array) config('data-rights.routes.public', []);
        $dashboard = (array) config('data-rights.routes.dashboard', []);
        $publicPrefix = (string) ($public['prefix'] ?? 'data-rights');

        if (($public['enabled'] ?? true) || ($dashboard['enabled'] ?? true)) {
            Route::get($publicPrefix.'/assets/{file}', AssetController::class)
                ->where('file', '[a-z0-9.-]+')
                ->name('data-rights.asset');
        }

        if ($public['enabled'] ?? true) {
            Route::middleware((array) ($public['middleware'] ?? ['web']))
                ->prefix($publicPrefix)
                ->name('data-rights.')
                ->group(__DIR__.'/../routes/public.php');
        }

        if ($dashboard['enabled'] ?? true) {
            Route::middleware([...(array) ($dashboard['middleware'] ?? ['web', 'auth']), 'can:'.self::GATE])
                ->prefix((string) ($dashboard['prefix'] ?? 'data-rights-desk'))
                ->name('data-rights-desk.')
                ->group(__DIR__.'/../routes/dashboard.php');
        }
    }
}
