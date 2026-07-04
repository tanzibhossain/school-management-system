<?php

namespace App\Http\Middleware;

use App\Modules\School\Services\ModuleSettingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for the optional modules (Payroll, LMS, Library, Transport, Messaging).
 * Usage: ->middleware('module.enabled:lms'). Disabled (or never-configured)
 * returns 403 with the exact message the DevPlan specifies, so a frontend can
 * distinguish "not enabled for your school" from a plain permissions 403.
 */
class CheckModuleEnabled
{
    public function __construct(
        private readonly ModuleSettingService $service,
    ) {}

    public function handle(Request $request, Closure $next, string $module): Response
    {
        $schoolId = app('current_school_id');

        if (! $schoolId || ! $this->service->isEnabled($schoolId, $module)) {
            abort(403, 'This module is not enabled for your school.');
        }

        return $next($request);
    }
}
