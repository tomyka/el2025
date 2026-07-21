<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Game;
use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GameSeeder extends Seeder
{
    public function run(): void
    {
        // Key collections: event_day → id, team name → id
        $events = Event::pluck('id', 'event_day');
        $teams = Team::pluck('id', 'team');

        $e1 = $events[1];
        $e2 = $events[2];
        $e3 = $events[3];

        // All times stored in UTC (source times are ET / UTC-4 in summer)
        $games = [

            // ── MATCHDAY 1 ────────────────────────────────────────────────────────────
            // Group A
            ['event_id' => $e1, 'home' => 'Mexico',                'away' => 'South Africa',          'date' => '2026-06-11 19:00:00'],
            ['event_id' => $e1, 'home' => 'Korea Republic',        'away' => 'Czechia',               'date' => '2026-06-12 02:00:00'],
            // Group B
            ['event_id' => $e1, 'home' => 'Canada',                'away' => 'Bosnia and Herzegovina', 'date' => '2026-06-12 19:00:00'],
            ['event_id' => $e1, 'home' => 'United States',         'away' => 'Paraguay',              'date' => '2026-06-13 01:00:00'],
            ['event_id' => $e1, 'home' => 'Qatar',                 'away' => 'Switzerland',           'date' => '2026-06-13 19:00:00'],
            // Group C
            ['event_id' => $e1, 'home' => 'Brazil',                'away' => 'Morocco',               'date' => '2026-06-13 22:00:00'],
            ['event_id' => $e1, 'home' => 'Haiti',                 'away' => 'Scotland',              'date' => '2026-06-14 01:00:00'],
            // Group D
            ['event_id' => $e1, 'home' => 'Australia',             'away' => 'Türkiye',               'date' => '2026-06-14 04:00:00'],
            // Group E
            ['event_id' => $e1, 'home' => 'Germany',               'away' => 'Curaçao',               'date' => '2026-06-14 17:00:00'],
            // Group F
            ['event_id' => $e1, 'home' => 'Netherlands',           'away' => 'Japan',                 'date' => '2026-06-14 20:00:00'],
            // Group E
            ['event_id' => $e1, 'home' => 'Ivory Coast',           'away' => 'Ecuador',               'date' => '2026-06-14 23:00:00'],
            // Group F
            ['event_id' => $e1, 'home' => 'Sweden',                'away' => 'Tunisia',               'date' => '2026-06-15 02:00:00'],
            // Group H
            ['event_id' => $e1, 'home' => 'Spain',                 'away' => 'Cape Verde',            'date' => '2026-06-15 17:00:00'],
            // Group G
            ['event_id' => $e1, 'home' => 'Belgium',               'away' => 'Egypt',                 'date' => '2026-06-15 22:00:00'],
            // Group H
            ['event_id' => $e1, 'home' => 'Saudi Arabia',          'away' => 'Uruguay',               'date' => '2026-06-15 22:00:00'],
            // Group G
            ['event_id' => $e1, 'home' => 'Iran',                  'away' => 'New Zealand',           'date' => '2026-06-16 04:00:00'],
            // Group I
            ['event_id' => $e1, 'home' => 'France',                'away' => 'Senegal',               'date' => '2026-06-16 19:00:00'],
            ['event_id' => $e1, 'home' => 'Iraq',                  'away' => 'Norway',                'date' => '2026-06-16 22:00:00'],
            // Group J
            ['event_id' => $e1, 'home' => 'Argentina',             'away' => 'Algeria',               'date' => '2026-06-17 01:00:00'],
            ['event_id' => $e1, 'home' => 'Austria',               'away' => 'Jordan',                'date' => '2026-06-17 04:00:00'],
            // Group K
            ['event_id' => $e1, 'home' => 'Portugal',              'away' => 'Congo DR',              'date' => '2026-06-17 17:00:00'],
            // Group L
            ['event_id' => $e1, 'home' => 'England',               'away' => 'Croatia',               'date' => '2026-06-17 20:00:00'],
            ['event_id' => $e1, 'home' => 'Ghana',                 'away' => 'Panama',                'date' => '2026-06-17 23:00:00'],
            // Group K
            ['event_id' => $e1, 'home' => 'Uzbekistan',            'away' => 'Colombia',              'date' => '2026-06-18 02:00:00'],

            // ── MATCHDAY 2 ────────────────────────────────────────────────────────────
            // Group A
            ['event_id' => $e2, 'home' => 'Czechia',               'away' => 'South Africa',          'date' => '2026-06-18 16:00:00'],
            // Group B
            ['event_id' => $e2, 'home' => 'Switzerland',           'away' => 'Bosnia and Herzegovina', 'date' => '2026-06-18 19:00:00'],
            ['event_id' => $e2, 'home' => 'Canada',                'away' => 'Qatar',                 'date' => '2026-06-18 22:00:00'],
            // Group A
            ['event_id' => $e2, 'home' => 'Mexico',                'away' => 'Korea Republic',        'date' => '2026-06-19 03:00:00'],
            // Group D
            ['event_id' => $e2, 'home' => 'United States',         'away' => 'Australia',             'date' => '2026-06-19 19:00:00'],
            // Group C
            ['event_id' => $e2, 'home' => 'Scotland',              'away' => 'Morocco',               'date' => '2026-06-19 22:00:00'],
            ['event_id' => $e2, 'home' => 'Brazil',                'away' => 'Haiti',                 'date' => '2026-06-20 01:00:00'],
            // Group D
            ['event_id' => $e2, 'home' => 'Türkiye',               'away' => 'Paraguay',              'date' => '2026-06-20 04:00:00'],
            // Group F
            ['event_id' => $e2, 'home' => 'Netherlands',           'away' => 'Sweden',                'date' => '2026-06-20 17:00:00'],
            // Group E
            ['event_id' => $e2, 'home' => 'Germany',               'away' => 'Ivory Coast',           'date' => '2026-06-20 20:00:00'],
            ['event_id' => $e2, 'home' => 'Ecuador',               'away' => 'Curaçao',               'date' => '2026-06-21 00:00:00'],
            // Group F
            ['event_id' => $e2, 'home' => 'Tunisia',               'away' => 'Japan',                 'date' => '2026-06-21 04:00:00'],
            // Group H
            ['event_id' => $e2, 'home' => 'Spain',                 'away' => 'Saudi Arabia',          'date' => '2026-06-21 16:00:00'],
            // Group G
            ['event_id' => $e2, 'home' => 'Belgium',               'away' => 'Iran',                  'date' => '2026-06-21 19:00:00'],
            // Group H
            ['event_id' => $e2, 'home' => 'Uruguay',               'away' => 'Cape Verde',            'date' => '2026-06-21 22:00:00'],
            // Group G
            ['event_id' => $e2, 'home' => 'New Zealand',           'away' => 'Egypt',                 'date' => '2026-06-22 01:00:00'],
            // Group J
            ['event_id' => $e2, 'home' => 'Argentina',             'away' => 'Austria',               'date' => '2026-06-22 17:00:00'],
            // Group I
            ['event_id' => $e2, 'home' => 'France',                'away' => 'Iraq',                  'date' => '2026-06-22 21:00:00'],
            ['event_id' => $e2, 'home' => 'Norway',                'away' => 'Senegal',               'date' => '2026-06-23 00:00:00'],
            // Group J
            ['event_id' => $e2, 'home' => 'Jordan',                'away' => 'Algeria',               'date' => '2026-06-23 03:00:00'],
            // Group K
            ['event_id' => $e2, 'home' => 'Portugal',              'away' => 'Uzbekistan',            'date' => '2026-06-23 17:00:00'],
            // Group L
            ['event_id' => $e2, 'home' => 'England',               'away' => 'Ghana',                 'date' => '2026-06-23 20:00:00'],
            ['event_id' => $e2, 'home' => 'Panama',                'away' => 'Croatia',               'date' => '2026-06-23 23:00:00'],
            // Group K
            ['event_id' => $e2, 'home' => 'Colombia',              'away' => 'Congo DR',              'date' => '2026-06-24 02:00:00'],

            // ── MATCHDAY 3 (pairs simultaneous within each group) ─────────────────────
            // Group B
            ['event_id' => $e3, 'home' => 'Switzerland',           'away' => 'Canada',                'date' => '2026-06-24 19:00:00'],
            ['event_id' => $e3, 'home' => 'Bosnia and Herzegovina', 'away' => 'Qatar',                'date' => '2026-06-24 19:00:00'],
            // Group C
            ['event_id' => $e3, 'home' => 'Scotland',              'away' => 'Brazil',                'date' => '2026-06-24 22:00:00'],
            ['event_id' => $e3, 'home' => 'Morocco',               'away' => 'Haiti',                 'date' => '2026-06-24 22:00:00'],
            // Group A
            ['event_id' => $e3, 'home' => 'Czechia',               'away' => 'Mexico',                'date' => '2026-06-25 01:00:00'],
            ['event_id' => $e3, 'home' => 'South Africa',          'away' => 'Korea Republic',        'date' => '2026-06-25 01:00:00'],
            // Group E
            ['event_id' => $e3, 'home' => 'Ecuador',               'away' => 'Germany',               'date' => '2026-06-25 20:00:00'],
            ['event_id' => $e3, 'home' => 'Curaçao',               'away' => 'Ivory Coast',           'date' => '2026-06-25 20:00:00'],
            // Group F
            ['event_id' => $e3, 'home' => 'Japan',                 'away' => 'Sweden',                'date' => '2026-06-25 23:00:00'],
            ['event_id' => $e3, 'home' => 'Tunisia',               'away' => 'Netherlands',           'date' => '2026-06-25 23:00:00'],
            // Group D
            ['event_id' => $e3, 'home' => 'Türkiye',               'away' => 'United States',         'date' => '2026-06-26 02:00:00'],
            ['event_id' => $e3, 'home' => 'Paraguay',              'away' => 'Australia',             'date' => '2026-06-26 02:00:00'],
            // Group I
            ['event_id' => $e3, 'home' => 'Norway',                'away' => 'France',                'date' => '2026-06-26 19:00:00'],
            ['event_id' => $e3, 'home' => 'Senegal',               'away' => 'Iraq',                  'date' => '2026-06-26 19:00:00'],
            // Group H
            ['event_id' => $e3, 'home' => 'Cape Verde',            'away' => 'Saudi Arabia',          'date' => '2026-06-27 00:00:00'],
            ['event_id' => $e3, 'home' => 'Uruguay',               'away' => 'Spain',                 'date' => '2026-06-27 00:00:00'],
            // Group G
            ['event_id' => $e3, 'home' => 'Egypt',                 'away' => 'Iran',                  'date' => '2026-06-27 03:00:00'],
            ['event_id' => $e3, 'home' => 'New Zealand',           'away' => 'Belgium',               'date' => '2026-06-27 03:00:00'],
            // Group L
            ['event_id' => $e3, 'home' => 'Panama',                'away' => 'England',               'date' => '2026-06-27 21:00:00'],
            ['event_id' => $e3, 'home' => 'Croatia',               'away' => 'Ghana',                 'date' => '2026-06-27 21:00:00'],
            // Group K
            ['event_id' => $e3, 'home' => 'Colombia',              'away' => 'Portugal',              'date' => '2026-06-27 23:30:00'],
            ['event_id' => $e3, 'home' => 'Congo DR',              'away' => 'Uzbekistan',            'date' => '2026-06-27 23:30:00'],
            // Group J
            ['event_id' => $e3, 'home' => 'Algeria',               'away' => 'Austria',               'date' => '2026-06-28 02:00:00'],
            ['event_id' => $e3, 'home' => 'Jordan',                'away' => 'Argentina',             'date' => '2026-06-28 02:00:00'],
        ];

        $rows = [];
        foreach ($games as $g) {
            $homeId = $teams->get($g['home'])
                ?? throw new \RuntimeException("Team not found in DB: \"{$g['home']}\"");
            $awayId = $teams->get($g['away'])
                ?? throw new \RuntimeException("Team not found in DB: \"{$g['away']}\"");

            $rows[] = [
                'event_id' => $g['event_id'],
                'home_team_id' => $homeId,
                'away_team_id' => $awayId,
                'game_date' => $g['date'],
            ];
        }

        DB::table((new Game)->getTable())->insert($rows);
    }
}
