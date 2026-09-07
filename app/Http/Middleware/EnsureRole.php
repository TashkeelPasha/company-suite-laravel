<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-side role gate. Unused today because role gating lives in JS
 * (see resources/js/auth-gate.js), but kept here so the backend dev can wire it
 * into API routes if they consolidate the API into this same Laravel app.
 *
 * Usage:
 *   Route::get(...)->middleware('role:superadmin');
 *   Route::get(...)->middleware('role:company,station');   // any of these
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                Auth::shouldUse($guard);
                return $next($request);
            }
        }

        return response()->json(['error' => 'Not authenticated'], 401);
    }
}
