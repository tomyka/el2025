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
        $teams = array(
                    array('team' => 'Anadolu Efes',     'link' => 'https://www.euroleaguebasketball.net/en/euroleague/teams/anadolu-efes-istanbul/roster/ist/?season=2025-26'),
                    array('team' => 'AS Monaco',        'link' => 'https://www.euroleaguebasketball.net/en/euroleague/teams/as-monaco/roster/mco/?season=2025-26'),
                    array('team' => 'Baskonia Vitoria', 'link' => 'https://www.euroleaguebasketball.net/en/euroleague/teams/baskonia-vitoria-gasteiz/roster/bas/?season=2025-26'),
                    array('team' => 'Crvena Zvezda',    'link' => 'https://www.euroleaguebasketball.net/en/euroleague/teams/crvena-zvezda-meridianbet-belgrade/roster/red/?season=2025-26'),
                    array('team' => 'Dubai Basketball', 'link' => 'https://www.euroleaguebasketball.net/en/euroleague/teams/dubai-basketball/roster/dub/?season=2025-26'),
                    array('team' => 'Emporio Armani',   'link' => 'https://www.euroleaguebasketball.net/en/euroleague/teams/ea7-emporio-armani-milan/roster/mil/?season=2025-26'),
                    array('team' => 'FC Barcelona',     'link' => 'https://www.euroleaguebasketball.net/en/euroleague/teams/fc-barcelona/roster/bar/?season=2025-26'),
                    array('team' => 'FC Bayern Munich', 'link' => 'https://www.euroleaguebasketball.net/en/euroleague/teams/fc-bayern-munich/roster/mun/?season=2025-26'),
                    array('team' => 'Fenerbahce',       'link' => 'https://www.euroleaguebasketball.net/en/euroleague/teams/fenerbahce-beko-istanbul/roster/ulk/?season=2025-26'),
                    array('team' => 'Hapoel',           'link' => 'https://www.euroleaguebasketball.net/en/euroleague/teams/hapoel-ibi-tel-aviv/roster/hta/?season=2025-26'),
                    array('team' => 'LDLC Asvel',       'link' => 'https://www.euroleaguebasketball.net/en/euroleague/teams/ldlc-asvel-villeurbanne/roster/asv/?season=2025-26'),
                    array('team' => 'Maccabi',          'link' => 'https://www.euroleaguebasketball.net/en/euroleague/teams/maccabi-playtika-tel-aviv/roster/tel/?season=2025-26'),
                    array('team' => 'Olympiacos',       'link' => 'https://www.euroleaguebasketball.net/en/euroleague/teams/olympiacos-piraeus/roster/oly/?season=2025-26'),
                    array('team' => 'Panathinaikos',    'link' => 'https://www.euroleaguebasketball.net/en/euroleague/teams/panathinaikos-aktor-athens/roster/pan/?season=2025-26'),
                    array('team' => 'Paris Basketball', 'link' => 'https://www.euroleaguebasketball.net/en/euroleague/teams/paris-basketball/roster/prs/?season=2025-26'),
                    array('team' => 'Partizan',         'link' => 'https://www.euroleaguebasketball.net/en/euroleague/teams/partizan-mozzart-bet-belgrade/roster/par/?season=2025-26'),
                    array('team' => 'Real Madrid',      'link' => 'https://www.euroleaguebasketball.net/en/euroleague/teams/real-madrid/roster/mad/?season=2025-26'),
                    array('team' => 'Valencia',         'link' => 'https://www.euroleaguebasketball.net/en/euroleague/teams/valencia-basket/roster/pam/?season=2025-26'),
                    array('team' => 'Virtus',           'link' => 'https://www.euroleaguebasketball.net/en/euroleague/teams/virtus-segafredo-bologna/roster/vir/?season=2025-26'),
                    array('team' => 'Zalgiris',         'link' => 'https://www.euroleaguebasketball.net/en/euroleague/teams/zalgiris-kaunas/roster/zal/?season=2025-26')
         );

        DB::table((new Team)->getTable())->insert($teams);

    }
}
