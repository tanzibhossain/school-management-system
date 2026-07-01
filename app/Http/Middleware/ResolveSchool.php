<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveSchool
{
    /**
     * Resolve the current school from the request host and bind it to the app.
     *
     * Super-admin routes (prefixed with /api/v2/platform) bypass this check.
     * All other API routes require a resolvable school.
     *
     * TODO (School module): Replace the placeholder with real lookup:
     *   $school = \App\Modules\School\Models\School::where('domain', $host)
     *       ->orWhere('subdomain', $subdomain)
     *       ->firstOrFail();
     *   app()->instance('current_school', $school);
     *   app()->instance('current_school_id', $school->id);
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Platform-level routes do not require a school context.
        if ($request->is('api/v2/platform/*') || $request->is('api/v2/health')) {
            return $next($request);
        }

        // ── Placeholder until the School module is built ──────────────────────
        // Once School module exists, uncomment the real lookup above and remove
        // the two lines below.
        app()->instance('current_school_id', null);

        return $next($request);
    }
}
