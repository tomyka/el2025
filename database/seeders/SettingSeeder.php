<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $settings = [
            ['setting' => 'survivalGame', 'value' => '0'],
            ['setting' => 'timeDifference', 'value' => '3'],
        ];

        DB::table((new Setting)->getTable())->insert($settings);

    }
}
