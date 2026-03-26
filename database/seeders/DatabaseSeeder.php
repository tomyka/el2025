<?php

namespace Database\Seeders;

use App\Models\Group;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call(
            ColorSeeder::class,
        );

        $this->call(
            GroupSeeder::class,
        );

        $this->call(
            SettingSeeder::class,
        );

        $this->call(
            TeamSeeder::class,
        );

        $this->call(
            EventSeeder::class,
        );

    }
}
