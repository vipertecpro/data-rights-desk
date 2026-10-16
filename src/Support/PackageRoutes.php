<?php

namespace Vipertecpro\DataRightsDesk\Support;

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;

/**
 * The route names the package registers, so the checker can tell when an app
 * route has taken one of the package's addresses or names. The app's routes
 * load after the package's, so on a clash the app wins without a sound.
 */
final class PackageRoutes
{
    public const ASSET = 'data-rights.asset';

    public const PUBLIC = [
        'data-rights.index', 'data-rights.notice', 'data-rights.notice.version', 'data-rights.preferences',
        'data-rights.age', 'data-rights.consents.give', 'data-rights.consents.withdraw', 'data-rights.guardian',
        'data-rights.nominations.store', 'data-rights.nominations.destroy', 'data-rights.requests.index',
        'data-rights.requests.store', 'data-rights.requests.show', 'data-rights.requests.cancel',
        'data-rights.requests.download',
    ];

    public const API = [
        'data-rights.api.notice', 'data-rights.api.purposes', 'data-rights.api.consents.give',
        'data-rights.api.consents.withdraw', 'data-rights.api.requests.index', 'data-rights.api.requests.store',
        'data-rights.api.requests.show', 'data-rights.api.requests.download',
    ];

    public const DASHBOARD = [
        'data-rights-desk.index', 'data-rights-desk.check',
        'data-rights-desk.requests.index', 'data-rights-desk.requests.create', 'data-rights-desk.requests.store',
        'data-rights-desk.requests.show', 'data-rights-desk.requests.verify', 'data-rights-desk.requests.status',
        'data-rights-desk.requests.complete', 'data-rights-desk.requests.reject', 'data-rights-desk.requests.erase',
        'data-rights-desk.requests.summary',
        'data-rights-desk.consents.index', 'data-rights-desk.consents.lookup', 'data-rights-desk.consents.proof',
        'data-rights-desk.notices.index', 'data-rights-desk.notices.show', 'data-rights-desk.notices.publish',
        'data-rights-desk.breaches.index', 'data-rights-desk.breaches.create', 'data-rights-desk.breaches.store',
        'data-rights-desk.breaches.show', 'data-rights-desk.breaches.update', 'data-rights-desk.breaches.draft',
        'data-rights-desk.breaches.affected', 'data-rights-desk.breaches.notify',
        'data-rights-desk.breaches.board-alerted', 'data-rights-desk.breaches.board-reported',
        'data-rights-desk.processors.index', 'data-rights-desk.processors.create', 'data-rights-desk.processors.store',
        'data-rights-desk.processors.edit', 'data-rights-desk.processors.update',
        'data-rights-desk.children', 'data-rights-desk.children.age', 'data-rights-desk.children.lookup',
        'data-rights-desk.retention.index', 'data-rights-desk.retention.contact',
        'data-rights-desk.retention.holds.store', 'data-rights-desk.retention.holds.release',
        'data-rights-desk.api.summary', 'data-rights-desk.api.requests', 'data-rights-desk.api.requests.show',
        'data-rights-desk.api.consents', 'data-rights-desk.api.notices', 'data-rights-desk.api.breaches',
        'data-rights-desk.api.breaches.show', 'data-rights-desk.api.retention', 'data-rights-desk.api.check',
    ];

    /**
     * The names the enabled route groups register.
     *
     * @return list<string>
     */
    public static function expected(): array
    {
        $public = (bool) config('data-rights.routes.public.enabled', true);
        $api = (bool) config('data-rights.routes.api.enabled', true);
        $dashboard = (bool) config('data-rights.routes.dashboard.enabled', true);

        return [
            ...($public || $dashboard ? [self::ASSET] : []),
            ...($public ? self::PUBLIC : []),
            ...($api ? self::API : []),
            ...($dashboard ? self::DASHBOARD : []),
        ];
    }

    /**
     * Package routes that are gone (an app route took the same address, and the app's routes
     * load later, so it wins) and app routes that reuse a package route name (two routes with
     * one name break php artisan route:cache).
     *
     * @return array{missing: list<string>, taken: array<string, string>}
     */
    public static function clashes(): array
    {
        $routes = Route::getRoutes();
        $expected = self::expected();
        $missing = [];
        $taken = [];

        foreach ($expected as $name) {
            $route = $routes->getByName($name);

            if ($route === null || ! self::isPackageRoute($route)) {
                $missing[] = $name;
            }
        }

        foreach ($routes->getRoutes() as $route) {
            $name = $route->getName();

            if ($name !== null && in_array($name, $expected, true) && ! self::isPackageRoute($route)) {
                $taken[$name] = $route->uri();
            }
        }

        return ['missing' => array_values(array_diff($missing, array_keys($taken))), 'taken' => $taken];
    }

    private static function isPackageRoute(RoutingRoute $route): bool
    {
        return str_starts_with(ltrim($route->getActionName(), '\\'), 'Vipertecpro\\DataRightsDesk\\');
    }
}
