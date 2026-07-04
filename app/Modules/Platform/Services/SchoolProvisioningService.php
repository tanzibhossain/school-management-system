<?php

namespace App\Modules\Platform\Services;

use App\Models\User;
use App\Modules\Platform\Mail\SetPasswordMail;
use App\Modules\Platform\Models\Plan;
use App\Modules\School\Models\School;
use App\Modules\User\Services\UserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * The single choke point that creates a School + its first admin User TOGETHER —
 * something nothing in this codebase did before Platform (#23). All three
 * provisioning paths funnel through here: trial self-serve (immediate, no
 * payment), paid self-serve (called from the Stripe webhook once payment is
 * confirmed), and Super Admin offline/manual creation.
 */
class SchoolProvisioningService
{
    public function __construct(private readonly UserService $userService) {}

    /**
     * @param array{school_name: string, subdomain: string, admin_name: string, admin_email: string, country_code?: string|null} $schoolData
     */
    public function provision(
        array $schoolData,
        Plan $plan,
        string $provisioningType,
        ?\DateTimeInterface $trialEndsAt = null,
        ?\DateTimeInterface $subscriptionExpiresAt = null,
    ): School {
        return DB::transaction(function () use ($schoolData, $plan, $provisioningType, $trialEndsAt, $subscriptionExpiresAt): School {
            $school = School::create([
                'name' => $schoolData['school_name'],
                'subdomain' => $schoolData['subdomain'],
                'country_code' => $schoolData['country_code'] ?? null,
                'email' => $schoolData['admin_email'],
                'is_active' => true,
                'plan_id' => $plan->id,
                'trial_ends_at' => $trialEndsAt,
                'subscription_expires_at' => $subscriptionExpiresAt,
                'provisioning_type' => $provisioningType,
                'subscription_status' => $provisioningType === 'self_service' && $plan->slug === 'trial'
                    ? 'trialing'
                    : 'active',
            ]);

            $admin = $this->userService->createUser([
                'name' => $schoolData['admin_name'],
                'email' => $schoolData['admin_email'],
                // Unusable random password — never emailed. The admin sets their own
                // via the signed "set password" link below (confirmed decision: no
                // plaintext password is ever sent).
                'password' => Str::random(40),
                'role' => 'admin',
            ], $school->id);

            $this->sendSetPasswordEmail($admin, $school->name);

            // NOT ->fresh() — that would re-query and discard wasRecentlyCreated,
            // which JsonResource relies on to auto-return 201 (same gotcha noted
            // throughout this codebase). load() keeps the flag intact.
            return $school->load('plan');
        });
    }

    public function sendSetPasswordEmail(User $user, string $schoolName): void
    {
        $signedUrl = URL::temporarySignedRoute(
            'platform.set-password',
            now()->addDays(7),
            ['user' => $user->id],
        );

        Mail::to($user->email)->send(new SetPasswordMail($user, $schoolName, $signedUrl));
    }
}
