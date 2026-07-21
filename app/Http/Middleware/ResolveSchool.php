<?php

namespace App\Http\Middleware;

use App\Modules\School\Models\School;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ResolveSchool
{
    /**
     * Resolve the current school and bind it to the app container.
     *
     * Resolution priority:
     *  1. Authenticated user's school_id  (production path)
     *  2. First school in DB              (public endpoints / login)
     *
     * Platform routes (/api/v2/platform/*, /api/v2/health) are bypassed.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api/v2/platform/*') || $request->is('api/v2/health')) {
            return $next($request);
        }

        // Try to resolve from the authenticated user first
        $user = Auth::guard('sanctum')->user();
        $school = null;

        if ($user && $user->school_id) {
            $school = School::find($user->school_id);
        }

        // Fall back to first school (covers login + all public endpoints)
        if (! $school) {
            $school = School::first();
        }

        app()->instance('current_school', $school);
        app()->instance('current_school_id', $school?->id);

        return $next($request);
    }
}
