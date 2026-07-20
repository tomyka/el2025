<?php
namespace App\Http\Controllers;

use App\Models\LeagueMember;
use App\Models\UserSetting;
use App\Models\Setting;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use DateTime;

class SessionController extends Controller
{
    public function setSession($user): void
    {
        $userSettings = UserSetting::where('user_id', $user->id)->firstOrFail();
        $leagueMember = LeagueMember::where('user_id', $user->id)
            ->where('active', true)
            ->with('league.tournament')
            ->firstOrFail();

        $leagueID     = $leagueMember->league_id;
        $tournamentID = $leagueMember->league->tournament_id;
        $tournament   = $leagueMember->league->tournament;

        $event = DB::table('games')
            ->join('events', 'games.event_id', '=', 'events.id')
            ->select('events.id', 'events.event_survival', 'events.rate')
            ->whereNull('games.home_team_score')
            ->where('events.tournament_id', $tournamentID)
            ->first();

        $firstGame = DB::table('games')
            ->join('events', 'games.event_id', '=', 'events.id')
            ->where('events.tournament_id', $tournamentID)
            ->orderBy('games.game_date')
            ->select('games.game_date')
            ->first();

        $disabled = $firstGame
            ? (strtotime('-0 day', (new DateTime($firstGame->game_date))->getTimestamp()) < time() ? 'disabled' : '')
            : '';

        if ($event) {
            $eventID       = $event->id;
            $eventSurvival = $event->event_survival;
            $eventRate     = $event->rate;
        } else {
            $eventID = 0; $eventSurvival = 0; $eventRate = 0;
        }

        $timeDifference = Setting::where('setting', 'timeDifference')->first();

        Session::put('tournamentID',   $tournamentID);
        Session::put('active',         $user->active);
        Session::put('eventID',        $eventID);
        Session::put('eventSurvival',  $eventSurvival);
        Session::put('eventRate',      $eventRate);
        Session::put('disabled',       $disabled);
        Session::put('userID',         $user->id);
        Session::put('resultAmount',   $userSettings->result_amount);
        Session::put('leagueID',       $leagueID);
        Session::put('admin',          $userSettings->admin);
        Session::put('fee',            $leagueMember->league->base_fee);
        Session::put('guest',          (int) $leagueMember->is_guest);
        Session::put('survivalGame',   $tournament->survival_game ? 1 : 0);
        Session::put('timeDifference', $timeDifference?->value ?? 0);
        Session::put('locale',         $userSettings->locale ?? 'lt');
    }
}
