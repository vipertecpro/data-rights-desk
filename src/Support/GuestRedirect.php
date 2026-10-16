<?php

namespace Vipertecpro\DataRightsDesk\Support;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use ReflectionProperty;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;

/**
 * Where Laravel sends a guest who opens a signed-in page: the redirect set with
 * redirectGuestsTo(), which every Laravel 11+ app sets to route('login') unless it
 * changed it. Without a login route, the guest gets an error page.
 */
final class GuestRedirect
{
    public static function isBroken(): bool
    {
        try {
            $redirect = (new ReflectionProperty(Authenticate::class, 'redirectToCallback'))->getValue();
        } catch (Throwable) {
            return false;
        }

        try {
            if (is_callable($redirect)) {
                $redirect(Request::create('/'.trim((string) config('data-rights.routes.public.prefix', 'data-rights'), '/').'/preferences'));

                return false;
            }

            return ! Route::has('login');
        } catch (RouteNotFoundException) {
            return true;
        } catch (Throwable) {
            // The app's own redirect needs more than this check can give it: leave it be.
            return false;
        }
    }
}
