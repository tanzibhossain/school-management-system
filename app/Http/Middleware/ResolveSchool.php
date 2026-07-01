<?php

namespace App\Http\Middleware;

use App\Modules\School\Models\School;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveSchool
{
    /**
     * Resolve the current school and bind it to the app container.
     *
     * Platform-level routes (/api/v2/platform/* and /api/v2/health) bypass
     * this check and do not require a school context.
     *
     * All other API routes will have app('current_school') and
     * app('current_school_id') available after this middleware runs.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api/v2/platform/*') || $request->is('api/v2/health')) {
            return $next($request);
        }

        $school = School::first();

        app()->instance('current_school', $school);
        app()->instance('current_school_id', $school?->id);

        return $next($request);
    }
}
