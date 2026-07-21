<?php

namespace Database\Seeders;

use App\Modules\Language\Models\Language;
use Illuminate\Database\Seeder;

/**
 * Default languages: English (source + default) and Bangla (active, awaiting
 * translations). More are added from Settings → Languages.
 */
class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        Language::firstOrCreate(
            ['code' => 'en'],
            ['name' => 'English', 'native_name' => 'English', 'flag' => '🇬🇧', 'is_rtl' => false, 'is_active' => true, 'is_default' => true, 'sort_order' => 1],
        );
        Language::firstOrCreate(
            ['code' => 'bn'],
            ['name' => 'Bangla', 'native_name' => 'বাংলা', 'flag' => '🇧🇩', 'is_rtl' => false, 'is_active' => true, 'is_default' => false, 'sort_order' => 2],
        );
        Language::flushCache();
    }
}
