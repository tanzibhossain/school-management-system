<?php

namespace Tests\Feature\Payroll;

use App\Models\User;
use App\Modules\School\Models\ModuleSetting;
use App\Modules\School\Models\School;
use App\Modules\Staff\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class PayrollTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected School $school;

    protected User $staffUser;

    protected Staff $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->school = School::create(['name' => 'Test School', 'timezone' => 'UTC', 'currency' => 'USD', 'is_active' => true]);

        // Payroll now sits behind the school_module_settings toggle (retrofitted
        // alongside LMS) — enable it here so every pre-existing Payroll test
        // keeps exercising the routes instead of hitting a 403 from the new gate.
        ModuleSetting::create(['school_id' => $this->school->id, 'module' => 'payroll', 'is_enabled' => true]);

        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');

        $this->staffUser = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->staffUser->assignRole('teacher');
        $this->staff = Staff::create([
            'school_id' => $this->school->id,
            'user_id' => $this->staffUser->id,
            'name' => 'Staff One',
            'gender' => 'female',
            'status' => 'active',
            'joining_date' => now()->subYear()->toDateString(),
        ]);
    }

    protected function adminToken(): string
    {
        return $this->admin->createToken('test', ['*'])->plainTextToken;
    }

    protected function accountantToken(): string
    {
        $user = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $user->assignRole('accountant');

        return $user->createToken('test', ['accountant:*'])->plainTextToken;
    }

    /** Token for $this->staffUser — the real abilitiesForRole('teacher') result, since teacher:* now includes staff:*. */
    protected function staffToken(): string
    {
        return $this->staffUser->createToken('test', \App\Models\User::abilitiesForRole('teacher'))->plainTextToken;
    }

    protected function otherStaffToken(): array
    {
        $user = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $user->assignRole('teacher');
        $staff = Staff::create([
            'school_id' => $this->school->id,
            'user_id' => $user->id,
            'name' => 'Staff Two',
            'gender' => 'male',
            'status' => 'active',
        ]);

        return [$user->createToken('test', \App\Models\User::abilitiesForRole('teacher'))->plainTextToken, $staff];
    }
}
