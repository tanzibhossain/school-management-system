<?php

namespace App\Http\Controllers\Admin\Platform;

use App\Modules\Platform\Models\Plan;
use App\Modules\Platform\Models\SubscriptionReminder;
use App\Modules\Platform\Services\SuperAdminSchoolService;
use App\Modules\School\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Super-Admin portal (Blade) — cross-school, NOT under the `school` tenant
 * middleware. Thin wrapper over the Platform module's SuperAdminSchoolService.
 */
class SchoolController extends Controller
{
    public function __construct(private readonly SuperAdminSchoolService $service) {}

    public function index(): View
    {
        return view('platform.schools.index', ['schools' => $this->service->all()]);
    }

    public function create(): View
    {
        return view('platform.schools.create', ['plans' => Plan::orderBy('sort_order')->get()]);
    }

    /** Offline/manual provisioning — creates School + first admin, emails a set-password link (no Stripe). */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'school_name'             => ['required', 'string', 'max:150'],
            'subdomain'               => ['required', 'string', 'max:63', 'alpha_dash', 'lowercase', 'unique:schools,subdomain'],
            'admin_name'              => ['required', 'string', 'max:150'],
            'admin_email'             => ['required', 'email', 'max:150', 'unique:users,email'],
            'country_code'            => ['nullable', 'string', 'size:2'],
            'plan_id'                 => ['required', 'integer', 'exists:plans,id'],
            'subscription_expires_at' => ['required', 'date', 'after:today'],
        ]);

        try {
            $school = $this->service->createOffline(
                $data,
                (int) $data['plan_id'],
                new \DateTimeImmutable($data['subscription_expires_at']),
            );
        } catch (HttpExceptionInterface $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('platform.schools.show', $school->id)
            ->with('status', 'School provisioned — a set-password link was emailed to the admin.');
    }

    public function show(int $id): View
    {
        return view('platform.schools.show', [
            'school'    => School::with('plan')->findOrFail($id),
            'plans'     => Plan::orderBy('sort_order')->get(),
            'reminders' => SubscriptionReminder::where('school_id', $id)->orderByDesc('sent_at')->get(),
        ]);
    }

    public function updatePlan(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'plan_id'                 => ['required', 'integer', 'exists:plans,id'],
            'subscription_expires_at' => ['nullable', 'date'],
        ]);

        $school = School::findOrFail($id);

        try {
            $this->service->changePlan(
                $school,
                (int) $data['plan_id'],
                ! empty($data['subscription_expires_at']) ? new \DateTimeImmutable($data['subscription_expires_at']) : null,
            );
        } catch (HttpExceptionInterface $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Plan updated.');
    }
}
