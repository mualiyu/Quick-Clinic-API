<?php

namespace Database\Seeders;

use App\Models\LanguageSupport as ModelsLanguageSupport;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LanguageSupport extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = [
            ['language_name' => 'Hausa', 'language_code' => 'ha'],
            ['language_name' => 'Yoruba', 'language_code' => 'yo'],
            ['language_name' => 'Igbo', 'language_code' => 'ig'],
            ['language_name' => 'Fulfulde', 'language_code' => 'ff'],
            ['language_name' => 'Kanuri', 'language_code' => 'kr'],
            ['language_name' => 'Ijaw', 'language_code' => 'ij'],
            ['language_name' => 'Urhobo', 'language_code' => 'urh'],
            ['language_name' => 'Tiv', 'language_code' => 'tiv'],
            ['language_name' => 'Ibibio', 'language_code' => 'ibb'],
            ['language_name' => 'Edo', 'language_code' => 'bin'],
        ];

        foreach ($languages as $language) {
            ModelsLanguageSupport::create($language);
        }
    }
}
