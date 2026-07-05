<?php

namespace Tests\Feature\Library;

use App\Models\User;
use App\Modules\School\Models\ModuleSetting;
use App\Modules\School\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class LibraryTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->school = School::create([
            'name' => 'Library Test School',
            'is_active' => true,
        ]);

        ModuleSetting::create([
            'school_id' => $this->school->id,
            'module' => 'library',
            'is_enabled' => true,
        ]);

        $this->admin = User::factory()->create([
            'school_id' => $this->school->id,
            'is_active' => true,
        ]);
        $this->admin->assignRole('admin');
    }

    protected function adminToken(): string
    {
        return $this->admin->createToken('test', ['*'])->plainTextToken;
    }
}
