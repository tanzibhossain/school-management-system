<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Web counterpart of ResolveSchool: resolves the tenant from the session-
 * authenticated user so the existing Services/Repositories (which read
 * app('current_school_id')) work unchanged from Blade controllers.
 */
class SetCurrentSchoolFromSession
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->instance('current_school_id', Auth::user()?->school_id);

        return $next($request);
    }
}
