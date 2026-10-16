<?php

namespace Vipertecpro\DataRightsDesk\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;
use Vipertecpro\DataRightsDesk\Facades\DataRights;

/**
 * consent:{purpose} and data-rights.consent:{purpose}: lets the request
 * through only when DataRights::allows() is true for the signed-in person.
 */
class RequireConsent
{
    public function handle(Request $request, Closure $next, string $purpose): Response
    {
        $user = $request->user();

        if (! $user instanceof Model) {
            throw new AuthenticationException;
        }

        if (DataRights::allows($user, $purpose)) {
            return $next($request);
        }

        $preferences = Route::has('data-rights.preferences') ? route('data-rights.preferences') : null;

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Consent is needed for this purpose.',
                'purpose' => $purpose,
                'preferences_url' => $preferences,
            ], 403);
        }

        if ($preferences === null) {
            abort(403, 'Consent is needed for this purpose.');
        }

        return redirect()->to($preferences);
    }
}
