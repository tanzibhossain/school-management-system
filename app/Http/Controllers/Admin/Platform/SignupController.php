<?php

namespace App\Http\Controllers\Admin\Platform;

use App\Modules\Platform\Models\PendingSchoolSignup;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

/**
 * Super-Admin portal (Blade) — read-only view of the paid self-serve staging rows
 * (the Stripe Checkout round-trip). Provisioning happens via webhook; this is
 * observability into in-flight and completed signups.
 */
class SignupController extends Controller
{
    public function index(): View
    {
        return view('platform.signups.index', [
            'signups' => PendingSchoolSignup::with(['plan', 'createdSchool'])
                ->orderByDesc('created_at')->get(),
        ]);
    }
}
