<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\School\Models\School;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::first();

        $admin = User::firstOrCreate(
            ['email' => 'admin@school.edu.bd'],
            [
                'school_id' => $school?->id,
                'name'      => 'System Admin',
                'password'  => Hash::make('Admin@1234'),
                'is_active' => true,
            ]
        );

        $admin->syncRoles(['admin']);

        $this->command->info("Admin user ready — email: admin@school.edu.bd / password: Admin@1234");
    }
}
