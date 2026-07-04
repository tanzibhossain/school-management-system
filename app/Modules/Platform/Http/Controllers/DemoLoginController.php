<?php

namespace App\Modules\Platform\Http\Controllers;

use App\Models\User;
use App\Modules\School\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DemoLoginController extends Controller
{
    /**
     * GET /v2/platform/demo — public. Returns the ONE shared demo school's
     * prefilled login credentials so a visitor can log straight in — this
     * replaces any "request a demo" contact-sales flow entirely (confirmed
     * decision). The password shown is a fixed, deliberately-public value
     * (config('platform.demo_password')), never the random unusable password
     * SchoolProvisioningService generates for real signups.
     */
    public function show(): JsonResponse
    {
        $school = School::where('is_demo', true)->first();

        if (! $school) {
            throw new NotFoundHttpException('Demo is not currently available.');
        }

        $admin = User::where('school_id', $school->id)->role('admin')->first();

        return response()->json([
            'school_name' => $school->name,
            'subdomain' => $school->subdomain,
            'login_email' => $admin?->email,
            'login_password' => config('platform.demo_password'),
            'resets_every_hours' => 14,
            'notice' => 'This is a shared demo — data resets every 14 hours.',
        ]);
    }
}
