<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePredictionResultRequest;
use App\Models\Event;
use App\Models\Game;
use App\Models\PredictionResult;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PredictionResultController extends Controller
{
    public function getPredictionResultsUser() {
        if (session('userID') != '') {
            $userID      = session('userID');
            $eventFilter = request()->integer('event', 0);

            // Default to the current active event
            if ($eventFilter === 0) {
                $eventFilter = (int) session('eventID', 0);
            }

            $raw = $this->getPredictionGamesGrouped($userID, $eventFilter ?: null);
            $groupedResults = collect($raw)
                ->groupBy('event_day')
                ->map(fn($day) => $day->groupBy(
                    fn($g) => Carbon::parse($g->game_date, 'UTC')->setTimezone('Europe/Vilnius')->format('Y-m-d')
                )->sortKeys()->map(fn($dayGames) => $dayGames->sortBy('game_date')));

            $events = Event::orderBy('id')->pluck('event', 'id');

            return view('prediction.results')
                ->with('groupedResults', $groupedResults)
                ->with('events', $events)
                ->with('selectedEvent', $eventFilter);
        } else {
            return redirect('/');
        }
    }

    private function getPredictionGamesGrouped($userID, ?int $eventId = null) {
        $nowUtc = Carbon::now('UTC')->format('Y-m-d H:i:s');
        $eventClause = $eventId ? 'AND g.event_id = ?' : '';
        $bindings = $eventId ? [$userID, $eventId] : [$userID];

        $rows = DB::select("SELECT DISTINCT
                prr.id,
                g.id as game_id,
                g.game_date,
                g.event_id,
                e.event as event_name,
                e.event_day,
                e.is_knockout,
                IFNULL(e.rate, 1) AS rate,
                ht.team AS home_team,
                ht.id AS home_team_id,
                ht.group_name,
                at.team AS away_team,
                at.id AS away_team_id,
                prr.home_team_score,
                prr.away_team_score,
                prr.game_winner_id,
                g.home_team_score  AS actual_home_score,
                g.away_team_score  AS actual_away_score,
                g.game_winner_id   AS actual_winner_id,
                ROUND(IFNULL(por.full_points,       0), 1) AS full_points,
                ROUND(IFNULL(por.winner_points,     0), 1) AS winner_points,
                ROUND(IFNULL(por.difference_points, 0), 1) AS difference_points,
                ROUND(IFNULL(por.bingo_points,      0), 1) AS bingo_points,
                ROUND(IFNULL(por.streak_bonus,      0), 1) AS streak_bonus,
                ROUND(IFNULL(go.home_odds, 0), 4) AS home_odds,
                ROUND(IFNULL(go.draw_odds, 0), 4) AS draw_odds,
                ROUND(IFNULL(go.away_odds, 0), 4) AS away_odds
            FROM prediction_results AS prr
                JOIN users u ON prr.user_id = u.id
                JOIN games g ON g.id = prr.game_id
                JOIN teams ht ON g.home_team_id = ht.id
                JOIN teams at ON g.away_team_id = at.id
                JOIN events e ON e.id = g.event_id
                LEFT JOIN point_results AS por ON por.user_id = prr.user_id AND por.game_id = prr.game_id
                LEFT JOIN game_odds go ON go.game_id = g.id
            WHERE
                prr.user_id = ?
                {$eventClause}
            ORDER BY g.event_id ASC, ht.group_name ASC, g.game_date ASC",
            $bindings);

        foreach ($rows as $row) {
            $row->locked = $row->game_date <= $nowUtc ? 1 : 0;
        }

        return $rows;
    }

    public function updatePredictionResultUser(UpdatePredictionResultRequest $request)
    {
        $userID = session('userID');
        $gameID = $request->input('gameID');
        $homeTeamScore = $request->input('homeTeamScore');
        $awayTeamScore = $request->input('awayTeamScore');
        $gameWinnerID = $request->input('gameWinnerID');

        $game = Game::where('id', $gameID)->first();
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s');

        if ($game->game_date > $now) {
            // Auto-reactivate a deactivated user only when they genuinely submit a prediction
            \App\Models\UserSetting::where('user_id', $userID)
                ->where('active', false)
                ->update(['active' => true]);
            $predictionResult = PredictionResult::where('id', $request->input('prediction_gameID'))
                ->where('user_id', $userID)
                ->firstOrFail();

            $oldHomeScore    = $predictionResult->home_team_score;
            $oldAwayScore    = $predictionResult->away_team_score;
            $oldGameWinnerId = $predictionResult->game_winner_id;

            $predictionResult->home_team_score = (($homeTeamScore == "") ? null : $homeTeamScore);
            $predictionResult->away_team_score = (($awayTeamScore == "") ? null : $awayTeamScore);
            $predictionResult->game_winner_id = (($gameWinnerID == "") ? null : $gameWinnerID);
            $predictionResult->generated = 0;
            $predictionResult->save();

            if ($homeTeamScore != "" && $awayTeamScore != "") {
                $auditPredictionGameController = new AuditPredictionGameController();
                $auditPredictionGameController->insertAuditPredictionGame(
                    $userID, $gameID,
                    $homeTeamScore, $awayTeamScore, $gameWinnerID,
                    $oldHomeScore, $oldAwayScore, $oldGameWinnerId
                );
                (new GameOddsController())->updateGameOdds($gameID);
            }
        }

        $gameOdds = \App\Models\GameOdds::where('game_id', $gameID)->first();
        return response()->json([
            'success'   => true,
            'home_odds' => $gameOdds ? (float) $gameOdds->home_odds : 0.0,
            'draw_odds' => $gameOdds ? (float) $gameOdds->draw_odds : 0.0,
            'away_odds' => $gameOdds ? (float) $gameOdds->away_odds : 0.0,
        ]);
    }



    public function insertPredictionResultsUser($user_id)
    {
        $games = Game::all();
        if ($games->count() > 0) {
            foreach ($games as $game) {
                $predictionResults[] = [
                    'user_id' => $user_id
                    , 'game_id' => $game->id
                ];
            }
            $predictionResult = new PredictionResult();
            $predictionResult:: insert($predictionResults);
        }
    }

    public function getPredictionResultsUserGroupEventDay($eventID, $groupID, $userID){
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s');
        $rows = DB::select('SELECT
                            g.id,
                              prr.id AS prediction_id,
                              prr.game_winner_id,
                              g.game_date,
                              ht.team AS home_team,
                              at.team AS away_team,
                              ht.id AS home_team_id,
                              at.id AS away_team_id,
                              g.home_team_score,
                              g.away_team_score,
                              prr.home_team_score AS p_home_team_score,
                              prr.away_team_score AS p_away_team_score,
                              IFNULL(por.difference_points,0) AS difference_points,
                              IFNULL(por.winner_points,0) AS winner_points,
                              IFNULL(por.bingo_points,0) AS bingo_points,
                              ROUND(IFNULL(por.odds, 0), 4) AS odds,
                              IFNULL(por.odds_points,0) AS odds_points,
                              ROUND(IFNULL(por.full_points,0),2) AS full_points,
                              ROUND(IFNULL(por.streak_bonus,0),2) AS streak_bonus,
                              e.is_knockout,
                              IFNULL(e.rate, 1) AS rate,
                              ROUND(IFNULL(go.home_odds, 0), 4) AS home_odds,
                              ROUND(IFNULL(go.draw_odds, 0), 4) AS draw_odds,
                              ROUND(IFNULL(go.away_odds, 0), 4) AS away_odds
                          FROM games AS g
                              JOIN events AS e ON e.id=g.event_id
                              JOIN prediction_results AS prr ON g.id=prr.game_id
                              JOIN users AS u ON u.id=prr.user_id
                              JOIN league_members AS lm ON u.id=lm.user_id
                              JOIN user_settings AS us ON u.id=us.user_id
                              JOIN teams AS ht ON g.home_team_id=ht.id
                              JOIN teams AS at ON g.away_team_id=at.id
                              LEFT JOIN point_results AS por ON prr.user_id=por.user_id AND prr.game_id=por.game_id
                              LEFT JOIN game_odds go ON go.game_id = g.id
                          WHERE
                              u.id = ?
                              AND e.id = ?
                              AND lm.league_id = ?
                          ORDER BY g.id ASC',
            [$userID, $eventID, $groupID]);

        foreach ($rows as $row) {
            if ($row->game_date > $now) {
                $row->odds = 0.0;
            }
        }

        return $rows;
    }

    public function getPredictionGamesProfile($groupID, $eventID){
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s');
        $guest = session('guest');
        $eventIDValue = ($eventID === '') ? 99 : (int) $eventID;

        $predictionGames = DB::select('select
                          pr.game_id,
                          u.username,
                          pr.user_id,
                          pr.home_team_score,
                          pr.away_team_score,
                          g.home_team_id,
                          g.away_team_id,
                          pr.game_winner_id,
                          ROUND(IFNULL(por.difference_points,0),1) AS difference_points,
                          ROUND(IFNULL(por.winner_points,0),1) AS winner_points,
                          ROUND(IFNULL(por.bingo_points,0),1) AS bingo_points,
                          ROUND(IFNULL(por.Odds,0),4) AS odds,
                          ROUND(IFNULL(por.odds_points,0),1) AS odds_points,
                          ROUND(IFNULL(por.full_points,0),1) AS full_points,
                          ROUND(IFNULL(por.streak_bonus,0),1) AS streak_bonus
                    from prediction_results as pr
                          join users as u ON u.id = pr.user_id
                          join user_settings as us ON us.user_id = pr.user_id
                          join games as g ON g.id = pr.game_id
                          join events AS e ON e.id = g.event_id
                          join league_members as lm on pr.user_id=lm.user_id AND lm.league_id = ? AND lm.is_guest <= ?
                          left join point_results as por on por.user_id=pr.user_id AND por.game_id=pr.game_id
                        where g.game_date < ? AND e.id <= ? AND us.active = 1
                        ORDER BY pr.user_id ASC, g.id DESC',
            [$groupID, $guest, $now, $eventIDValue]);

        return $predictionGames;
    }

    public function getPredictionGamesUserResultAmount($userID, $resultAmount){
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s');
        $predictionGamesUser = DB::select('SELECT
             DISTINCT
                prr.id,
                g.id as game_id,
                g.game_date,
                ht.team AS home_team,
                ht.link AS home_team_link,
                ht.id AS home_team_id,
                at.team AS away_team,
                at.link AS away_team_link,
                at.id AS away_team_id,
                prr.home_team_score as home_team_score,
                prr.away_team_score as away_team_score,
                prr.game_winner_id as game_winner_id,
                prr.prediction_date as prediction_date,
                ? as Now,
                e.active,
                e.rate
            from prediction_results as prr
                join users u on prr.user_id=u.id
                join games g on g.id=prr.game_id
                join teams ht on g.home_team_id=ht.id
                join teams at on g.away_team_id=at.id
                join events e on e.id=g.event_id
            where
                prr.user_id = ? and
                g.game_date > ?
            order by g.game_date asc, prr.id ASC
            LIMIT ?',
            [$now, $userID, $now, (int) $resultAmount]);

        return $predictionGamesUser;
    }



    public function showSingleGame(int $gameID): \Illuminate\Http\Response
    {
        $game = Game::with('home_team', 'away_team')->findOrFail($gameID);

        try {
            $sessionController = new SessionController();
            $sessionController->setSession(Auth::user());
        } catch (\Throwable) {
            session(['userID' => Auth::id()]);
        }

        $userID = session('userID');
        $now    = Carbon::now('UTC')->format('Y-m-d H:i:s');
        $locked = $game->game_date <= $now;

        $prediction = PredictionResult::where('game_id', $gameID)
            ->where('user_id', $userID)
            ->first();

        return response(view('prediction.game-single', compact('game', 'locked', 'prediction')));
    }

    public function getPredictionResultSummary() {

        $groupID = session('leagueID');
        $eventID = session('eventID');

        $nowUtc = Carbon::now('UTC')->format('Y-m-d H:i:s');
        $games = DB::select('select
                                g.id
                               ,ht.team  AS home_team
                               ,at.team  AS away_team
                               ,g.home_team_id
                               ,g.away_team_id
                               ,g.home_team_score
                               ,g.away_team_score
                               ,g.game_winner_id
                               ,e.id    AS event_id
                               ,e.event AS event_name
                               ,e.is_knockout
                               ,g.game_date
                             from games AS g
                               join teams  AS ht ON g.home_team_id = ht.id
                               join teams  AS at ON g.away_team_id = at.id
                               join events AS e  ON e.id = g.event_id
                             where g.game_date < ?
                             order by g.id ASC
                             ', [$nowUtc]);

        $predictionResultController = new PredictionResultController();
        $predictionResults = $predictionResultController->getPredictionGamesProfile($groupID, $eventID);



        return view('summary.results')
            ->with('games', $games)
            ->with('predictionResults', $predictionResults)
            ->with('activeEventID', (int) $eventID);
    }
}

