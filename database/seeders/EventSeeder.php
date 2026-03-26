<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 34; $i++) {
            $events[] = [
                'event' => 'Reguliarus sezonas ' . $i . ' turas',
                'event_day' => $i,
                'event_survival' => '1',
                'active' => '1',
                'rate' => '1',
            ];
        }

        DB::table((new Event)->getTable())->insert($events);

    }
}
