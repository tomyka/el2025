<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeamSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $base = 'https://www.fifa.com/en/tournaments/mens/worldcup/canadamexicousa2026/teams/';
        $suffix = '/team-news';

        $teams = [
            // AFC (9)
            ['team' => 'Australia',             'link' => $base . 'australia'              . $suffix],
            ['team' => 'Iran',                  'link' => $base . 'iran'                   . $suffix],
            ['team' => 'Iraq',                  'link' => $base . 'iraq'                   . $suffix],
            ['team' => 'Japan',                 'link' => $base . 'japan'                  . $suffix],
            ['team' => 'Jordan',                'link' => $base . 'jordan'                 . $suffix],
            ['team' => 'Qatar',                 'link' => $base . 'qatar'                  . $suffix],
            ['team' => 'Saudi Arabia',          'link' => $base . 'saudi-arabia'           . $suffix],
            ['team' => 'Korea Republic',        'link' => $base . 'korea-republic'         . $suffix],
            ['team' => 'Uzbekistan',            'link' => $base . 'uzbekistan'             . $suffix],

            // CAF (10)
            ['team' => 'Algeria',               'link' => $base . 'algeria'                . $suffix],
            ['team' => 'Cape Verde',            'link' => $base . 'cape-verde'             . $suffix],
            ['team' => 'Congo DR',              'link' => $base . 'congo-dr'               . $suffix],
            ['team' => 'Egypt',                 'link' => $base . 'egypt'                  . $suffix],
            ['team' => 'Ghana',                 'link' => $base . 'ghana'                  . $suffix],
            ['team' => 'Ivory Coast',           'link' => $base . 'ivory-coast'            . $suffix],
            ['team' => 'Morocco',               'link' => $base . 'morocco'                . $suffix],
            ['team' => 'Senegal',               'link' => $base . 'senegal'                . $suffix],
            ['team' => 'South Africa',          'link' => $base . 'south-africa'           . $suffix],
            ['team' => 'Tunisia',               'link' => $base . 'tunisia'                . $suffix],

            // CONCACAF (6)
            ['team' => 'Canada',                'link' => $base . 'canada'                 . $suffix],
            ['team' => 'Curaçao',               'link' => $base . 'curacao'                . $suffix],
            ['team' => 'Haiti',                 'link' => $base . 'haiti'                  . $suffix],
            ['team' => 'Mexico',                'link' => $base . 'mexico'                 . $suffix],
            ['team' => 'Panama',                'link' => $base . 'panama'                 . $suffix],
            ['team' => 'United States',         'link' => $base . 'united-states'          . $suffix],

            // CONMEBOL (6)
            ['team' => 'Argentina',             'link' => $base . 'argentina'              . $suffix],
            ['team' => 'Brazil',                'link' => $base . 'brazil'                 . $suffix],
            ['team' => 'Colombia',              'link' => $base . 'colombia'               . $suffix],
            ['team' => 'Ecuador',               'link' => $base . 'ecuador'                . $suffix],
            ['team' => 'Paraguay',              'link' => $base . 'paraguay'               . $suffix],
            ['team' => 'Uruguay',               'link' => $base . 'uruguay'                . $suffix],

            // OFC (1)
            ['team' => 'New Zealand',           'link' => $base . 'new-zealand'            . $suffix],

            // UEFA (16)
            ['team' => 'Austria',               'link' => $base . 'austria'                . $suffix],
            ['team' => 'Belgium',               'link' => $base . 'belgium'                . $suffix],
            ['team' => 'Bosnia and Herzegovina','link' => $base . 'bosnia-and-herzegovina' . $suffix],
            ['team' => 'Croatia',               'link' => $base . 'croatia'                . $suffix],
            ['team' => 'Czechia',               'link' => $base . 'czechia'                . $suffix],
            ['team' => 'England',               'link' => $base . 'england'                . $suffix],
            ['team' => 'France',                'link' => $base . 'france'                 . $suffix],
            ['team' => 'Germany',               'link' => $base . 'germany'                . $suffix],
            ['team' => 'Netherlands',           'link' => $base . 'netherlands'            . $suffix],
            ['team' => 'Norway',                'link' => $base . 'norway'                 . $suffix],
            ['team' => 'Portugal',              'link' => $base . 'portugal'               . $suffix],
            ['team' => 'Scotland',              'link' => $base . 'scotland'               . $suffix],
            ['team' => 'Spain',                 'link' => $base . 'spain'                  . $suffix],
            ['team' => 'Sweden',                'link' => $base . 'sweden'                 . $suffix],
            ['team' => 'Switzerland',           'link' => $base . 'switzerland'            . $suffix],
            ['team' => 'Türkiye',               'link' => $base . 'turkiye'                . $suffix],
        ];

        DB::table((new Team)->getTable())->insert($teams);
    }
}
