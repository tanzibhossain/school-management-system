<?php

namespace Database\Seeders;

use App\Modules\School\Models\School;
use App\Modules\School\Models\SchoolOpeningHour;
use App\Modules\School\Models\SchoolPhone;
use App\Modules\Website\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        $profile = [
            'name'                        => 'Green Valley Model School',
            'established'                 => '1985-01-01',
            // Three configurable code fields (label + value)
            'institution_code_label'      => 'EIIN',
            'institution_code'            => '115394',
            'school_code_label'           => 'Technical Branch Code',
            'school_code'                 => '0000',
            'technical_branch_code_label' => 'School Code',
            'technical_branch_code'       => '5556',
            'address'                     => 'Natipota, Damurhuda, Chuadanga',
            'country_code'                => 'BD',
            'email'                       => 'info@greenvalley.edu.bd',
            'currency'                    => 'BDT',
            'timezone'                    => 'Asia/Dhaka',
            'locale'                      => 'en',
            'academic_year_pattern'       => 'jan_dec',
            'is_active'                   => true,
        ];

        // Single-tenant: update the sole school if it exists, else create it.
        $school = School::first();
        $school ? $school->update($profile) : $school = School::create($profile);

        // Public-site appearance defaults.
        SiteSetting::updateOrCreate(
            ['school_id' => $school->id],
            [
                'primary_color'     => '#0a6b2f',
                'accent_color'      => '#f59e0b',
                'heading_color'     => '#0f172a',
                'topbar_text_color' => '#ffffff',
                'ticker_position'   => 'below_nav',
                'meta_title'        => 'Green Valley Model School',
                'meta_description'  => 'A traditional institution nurturing curious minds since 1985.',
            ],
        );

        // Contact numbers — both shown (clickable) in the site header.
        SchoolPhone::where('school_id', $school->id)->delete();
        foreach ([
            ['phone' => '01309115394', 'is_primary' => true,  'show_in_header' => true],
            ['phone' => '01710866871', 'is_primary' => false, 'show_in_header' => true],
        ] as $phone) {
            SchoolPhone::create($phone + ['school_id' => $school->id]);
        }

        // Bangladesh template: weekend = Friday + Saturday; Sunday–Thursday are school days.
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
