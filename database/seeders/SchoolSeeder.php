<?php

namespace Database\Seeders;

use App\Modules\School\Models\School;
use App\Modules\School\Models\SchoolOpeningHour;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::firstOrCreate(
            ['name' => 'Demo School'],
            [
                // Bangladesh seed template — locale values come from here, never from code
                'institution_code'       => null,
                'institution_code_label' => 'EIIN',
                'school_code'            => null,
                'address'                => '123 School Street, Dhaka',
                'country_code'           => 'BD',
                'email'                  => 'info@demoschool.edu.bd',
                'currency'               => 'BDT',
                'timezone'               => 'Asia/Dhaka',
                'locale'                 => 'en',
                'academic_year_pattern'  => 'jan_dec',
                'is_active'              => true,
            ]
        );

        // Bangladesh template: weekend = Friday + Saturday; Sunday–Thursday are school days
        $defaults = [
            0 => ['is_open' => true,  'open_time' => '08:00', 'close_time' => '16:00'], // Sunday
            1 => ['is_open' => true,  'open_time' => '08:00', 'close_time' => '16:00'], // Monday
            2 => ['is_open' => true,  'open_time' => '08:00', 'close_time' => '16:00'], // Tuesday
            3 => ['is_open' => true,  'open_time' => '08:00', 'close_time' => '16:00'], // Wednesday
            4 => ['is_open' => true,  'open_time' => '08:00', 'close_time' => '16:00'], // Thursday
            5 => ['is_open' => false, 'open_time' => null,    'close_time' => null],    // Friday
            6 => ['is_open' => false, 'open_time' => null,    'close_time' => null],    // Saturday
        ];

        foreach ($defaults as $day => $hours) {
            SchoolOpeningHour::updateOrCreate(
                ['school_id' => $school->id, 'day_of_week' => $day],
                $hours,
            );
        }
    }
}
