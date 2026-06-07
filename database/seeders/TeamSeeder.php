<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $base   = 'https://www.fifa.com/en/tournaments/mens/worldcup/canadamexicousa2026/teams/';
        $suffix = '/team-news';

        $teams = [
            // Group A
            ['group_name' => 'A', 'team' => 'Mexico',           'link' => $base . 'mexico'                 . $suffix],
            ['group_name' => 'A', 'team' => 'South Africa',     'link' => $base . 'south-africa'           . $suffix],
            ['group_name' => 'A', 'team' => 'Korea Republic',   'link' => $base . 'korea-republic'         . $suffix],
            ['group_name' => 'A', 'team' => 'Czechia',          'link' => $base . 'czechia'                . $suffix],

            // Group B
            ['group_name' => 'B', 'team' => 'Canada',           'link' => $base . 'canada'                 . $suffix],
            ['group_name' => 'B', 'team' => 'Bosnia and Herzegovina', 'link' => $base . 'bosnia-herzegovina' . $suffix],
            ['group_name' => 'B', 'team' => 'Qatar',            'link' => $base . 'qatar'                  . $suffix],
            ['group_name' => 'B', 'team' => 'Switzerland',      'link' => $base . 'switzerland'            . $suffix],

            // Group C
            ['group_name' => 'C', 'team' => 'Brazil',           'link' => $base . 'brazil'                 . $suffix],
            ['group_name' => 'C', 'team' => 'Morocco',          'link' => $base . 'morocco'                . $suffix],
            ['group_name' => 'C', 'team' => 'Haiti',            'link' => $base . 'haiti'                  . $suffix],
            ['group_name' => 'C', 'team' => 'Scotland',         'link' => $base . 'scotland'               . $suffix],

            // Group D
            ['group_name' => 'D', 'team' => 'United States',    'link' => $base . 'usa'          . $suffix],
            ['group_name' => 'D', 'team' => 'Paraguay',         'link' => $base . 'paraguay'               . $suffix],
            ['group_name' => 'D', 'team' => 'Australia',        'link' => $base . 'australia'              . $suffix],
            ['group_name' => 'D', 'team' => 'Türkiye',          'link' => $base . 'turkiye'                . $suffix],

            // Group E
            ['group_name' => 'E', 'team' => 'Germany',          'link' => $base . 'germany'                . $suffix],
            ['group_name' => 'E', 'team' => 'Curaçao',          'link' => $base . 'curacao'                . $suffix],
            ['group_name' => 'E', 'team' => 'Ivory Coast',      'link' => $base . 'cote-d-ivoire'            . $suffix],
            ['group_name' => 'E', 'team' => 'Ecuador',          'link' => $base . 'ecuador'                . $suffix],

            // Group F
            ['group_name' => 'F', 'team' => 'Netherlands',      'link' => $base . 'netherlands'            . $suffix],
            ['group_name' => 'F', 'team' => 'Japan',            'link' => $base . 'japan'                  . $suffix],
            ['group_name' => 'F', 'team' => 'Sweden',           'link' => $base . 'sweden'                 . $suffix],
            ['group_name' => 'F', 'team' => 'Tunisia',          'link' => $base . 'tunisia'                . $suffix],

            // Group G
            ['group_name' => 'G', 'team' => 'Belgium',          'link' => $base . 'belgium'                . $suffix],
            ['group_name' => 'G', 'team' => 'Egypt',            'link' => $base . 'egypt'                  . $suffix],
            ['group_name' => 'G', 'team' => 'Iran',             'link' => $base . 'iran'                   . $suffix],
            ['group_name' => 'G', 'team' => 'New Zealand',      'link' => $base . 'new-zealand'            . $suffix],

            // Group H
            ['group_name' => 'H', 'team' => 'Spain',            'link' => $base . 'spain'                  . $suffix],
            ['group_name' => 'H', 'team' => 'Cape Verde',       'link' => $base . 'cabo-verde'             . $suffix],
            ['group_name' => 'H', 'team' => 'Saudi Arabia',     'link' => $base . 'saudi-arabia'           . $suffix],
            ['group_name' => 'H', 'team' => 'Uruguay',          'link' => $base . 'uruguay'                . $suffix],

            // Group I
            ['group_name' => 'I', 'team' => 'France',           'link' => $base . 'france'                 . $suffix],
            ['group_name' => 'I', 'team' => 'Senegal',          'link' => $base . 'senegal'                . $suffix],
            ['group_name' => 'I', 'team' => 'Iraq',             'link' => $base . 'iraq'                   . $suffix],
            ['group_name' => 'I', 'team' => 'Norway',           'link' => $base . 'norway'                 . $suffix],

            // Group J
            ['group_name' => 'J', 'team' => 'Argentina',        'link' => $base . 'argentina'              . $suffix],
            ['group_name' => 'J', 'team' => 'Algeria',          'link' => $base . 'algeria'                . $suffix],
            ['group_name' => 'J', 'team' => 'Austria',          'link' => $base . 'austria'                . $suffix],
            ['group_name' => 'J', 'team' => 'Jordan',           'link' => $base . 'jordan'                 . $suffix],

            // Group K
            ['group_name' => 'K', 'team' => 'Portugal',         'link' => $base . 'portugal'               . $suffix],
            ['group_name' => 'K', 'team' => 'Congo DR',         'link' => $base . 'congo-dr'               . $suffix],
            ['group_name' => 'K', 'team' => 'Uzbekistan',       'link' => $base . 'uzbekistan'             . $suffix],
            ['group_name' => 'K', 'team' => 'Colombia',         'link' => $base . 'colombia'               . $suffix],

            // Group L
            ['group_name' => 'L', 'team' => 'England',          'link' => $base . 'england'                . $suffix],
            ['group_name' => 'L', 'team' => 'Croatia',          'link' => $base . 'croatia'                . $suffix],
            ['group_name' => 'L', 'team' => 'Ghana',            'link' => $base . 'ghana'                  . $suffix],
            ['group_name' => 'L', 'team' => 'Panama',           'link' => $base . 'panama'                 . $suffix],
        ];

        DB::table((new Team)->getTable())->insert($teams);
    }
}
