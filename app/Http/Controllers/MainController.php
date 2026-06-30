<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;



class MainController extends Controller
{
    public function loadApp(){


        $teamStatisticsController = new TeamStatisticsController();
        $feeController = new FeeController();
        $predictionResults = new PredictionResultController();
        $pointController = new PointController();
        $predictionSurvivalController = new PredictionSurvivalController();
        $predictionStandingController = new PredictionStandingController();
        $messageController = new MessageController();
        $activityFeedController = new ActivityFeedController();
        $user = Auth::user();
        $rankHistory = [];

        if (isset($user)) {
            $sessionController = new SessionController();
            $sessionController->setSession($user);
            $groupID = session('leagueID');
            $userID = session('userID');
            $eventID = session('eventID');
            $points = $pointController->getAllUserPoints($groupID);
            $rankHistory = $pointController->getRankHistory($groupID, $userID);
            $gameHistory = $pointController->getAllUsersGameHistory($groupID);
            foreach ($points as $i => &$point) {
                $history               = $gameHistory[$point['userID']] ?? [];
                $point['roundHistory'] = $history;
            }
            unset($point);
            $messages = $messageController->getProfileMessages($groupID);
            $predictionResults = $predictionResults->getPredictionResultsUserGroupEventDay($eventID,$groupID, $userID);
            $predictionResultsWithStats = $teamStatisticsController->prepareTeamStatistics($predictionResults);

            // Drop finished games from previous days; keep today's finished games
            $today = now()->toDateString();
            $predictionResultsWithStats = array_values(array_filter(
                $predictionResultsWithStats,
                fn($item) => $item['gameDetails']->home_team_score === null
                          || substr($item['gameDetails']->game_date, 0, 10) >= $today
            ));

            // Limit upcoming-games widget to first 3 distinct calendar dates
            $upcomingDates = collect($predictionResultsWithStats)
                ->map(fn($item) => substr($item['gameDetails']->game_date, 0, 10))
                ->unique()->take(3)->flip();
            $predictionResultsWithStats = array_values(array_filter(
                $predictionResultsWithStats,
                fn($item) => $upcomingDates->has(substr($item['gameDetails']->game_date, 0, 10))
            ));
            $standings = $predictionStandingController->getPredictionStandingTop4( $groupID);
            $eventDaySurvivalStatus=$predictionSurvivalController->getEventDaySurvivalStatus($userID,$eventID);
            $predictionStandingsPoints = $pointController->getPredictionStandingsUserPoints($userID);
            $firstGameStarted = DB::table('games')->where('game_date', '<=', now())->exists();

            $standingsMissing = $this->standingsMissing($userID);

            $tournamentProgress = $this->getTournamentProgress();

            $snapshot = $this->getSnapshotData($points, (int) session('userID'), $rankHistory);

            $activityFeed = $activityFeedController->getFeed((int) session('leagueID'));

            return view('main')->with('messages', $messages)->with('points', $points)->with('predictionGames', $predictionResultsWithStats)->with('eventDaySurvivalStatus',$eventDaySurvivalStatus)->with('groupDetails',$feeController->getGroupDetails())->with('userDetails',$feeController->getUserDetails())->with('fund',$feeController->getFund())->with('fundCollected',$feeController->getFundCollected())->with('standings',$standings)->with('predictionStandingsPoints',$predictionStandingsPoints)->with('rankHistory', $rankHistory)->with('firstGameStarted', $firstGameStarted)->with('standingsMissing', $standingsMissing)->with('tournamentProgress', $tournamentProgress)->with('snapshot', $snapshot)->with('activityFeed', $activityFeed);
        }
        else {
            $games = Game::with('away_team')->with('home_team')->take(9)->get();

            return view('main')->with('games',$games);
        }
    }

    public function leaderboard(): \Illuminate\View\View
    {
        $players = DB::select('
            SELECT
                u.username,
                ROUND(SUM(IFNULL(pr.full_points, 0) + IFNULL(pr.streak_bonus, 0)), 1) AS total_points,
                SUM(CASE WHEN pr.bingo_points >= 50 THEN 1 ELSE 0 END)               AS exact_predictions,
                SUM(CASE WHEN pr.winner_points  >= 5  THEN 1 ELSE 0 END)              AS correct_winners,
                COUNT(pr.id)                                                           AS games_predicted
            FROM users u
            JOIN user_settings us ON us.user_id = u.id
            JOIN point_results pr ON pr.user_id = u.id
            WHERE us.active = 1
            GROUP BY u.id, u.username
            HAVING total_points > 0
            ORDER BY total_points DESC
        ');

        return view('leaderboard', compact('players'));
    }

    private function standingsMissing(int $userID): bool
    {
        $total = DB::table('prediction_standings')->where('user_id', $userID)->count();
        if ($total === 0) return false;

        // group_position: every team must have one, and no duplicates within a group
        if (DB::table('prediction_standings')->where('user_id', $userID)->whereNull('group_position')->exists()) {
            return true;
        }
        $duplicatePositions = DB::table('prediction_standings as ps')
            ->join('teams as t', 'ps.team_id', '=', 't.id')
            ->where('ps.user_id', $userID)
            ->whereNotNull('ps.group_position')
            ->select('t.group_name', 'ps.group_position')
            ->groupBy('t.group_name', 'ps.group_position')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
        if ($duplicatePositions) return true;

        // knockout stage counts must match expected bracket sizes
        $counts = DB::table('prediction_standings')
            ->where('user_id', $userID)
            ->selectRaw('
                SUM(last32 = 1)       AS last32_count,
                SUM(last16 = 1)       AS last16_count,
                SUM(quarterfinal = 1) AS qf_count,
                SUM(semifinal = 1)    AS sf_count,
                SUM(final IS NOT NULL AND final BETWEEN 1 AND 4) AS final_count
            ')
            ->first();

        // Derive expected last32 count: if total teams >= 32 a round of 32 exists
        $expectedLast32 = $total >= 32 ? 32 : 0;
        if ($expectedLast32 > 0 && (int) $counts->last32_count !== $expectedLast32) return true;

        if ((int) $counts->last16_count !== 16) return true;
        if ((int) $counts->qf_count     !== 8)  return true;
        if ((int) $counts->sf_count     !== 4)  return true;
        if ((int) $counts->final_count  !== 4)  return true;

        return false;
    }

    private function getSnapshotData(array $points, int $userID, array $rankHistory = []): ?array
    {
        $rank = null;
        $mine = null;
        foreach ($points as $i => $p) {
            if ((int) $p['userID'] === $userID) {
                $rank = $i + 1;
                $mine = $p;
                break;
            }
        }
        if ($mine === null) return null;

        $last5 = DB::table('point_results as pr')
            ->join('games as g', 'g.id', '=', 'pr.game_id')
            ->where('pr.user_id', $userID)
            ->whereNotNull('g.home_team_score')
            ->whereNotNull('g.away_team_score')
            ->orderByDesc('g.game_date')
            ->orderByDesc('g.id')
            ->limit(5)
            ->select('pr.bingo_points', 'pr.winner_points')
            ->get()
            ->map(fn($r) => [
                'type' => $r->bingo_points > 0 ? 'bingo'
                        : ($r->winner_points > 0 ? 'win' : 'miss'),
            ])
            ->toArray();

        if (empty($last5)) return null;

        // Streak: consecutive non-miss games from most recent
        $allResults = DB::table('point_results as pr')
            ->join('games as g', 'g.id', '=', 'pr.game_id')
            ->where('pr.user_id', $userID)
            ->whereNotNull('g.home_team_score')
            ->whereNotNull('g.away_team_score')
            ->orderByDesc('g.game_date')
            ->orderByDesc('g.id')
            ->select('pr.bingo_points', 'pr.winner_points')
            ->get();

        $streak = 0;
        foreach ($allResults as $r) {
            if ($r->bingo_points > 0 || $r->winner_points > 0) {
                $streak++;
            } else {
                break;
            }
        }

        // Rank change over last 5 games
        $n = count($rankHistory);
        $rank_change = null;
        if ($n >= 2) {
            $prevIndex   = max(0, $n - 6);
            $prevRank    = $rankHistory[$prevIndex];
            $rank_change = $prevRank - $rank; // positive = improved
        }

        return [
            'rank'        => $rank,
            'total'       => round(
                ($mine['userGamePoints'] ?? 0)
                + ($mine['userStreakPoints'] ?? 0)
                + ($mine['standingPoints']->total_points ?? 0)
                + ($mine['survivalPoints'] ?? 0),
                1
            ),
            'bingo_count' => $mine['userGameBingo'] ?? 0,
            'streak'      => $streak,
            'rank_change' => $rank_change,
            'last5'       => $last5,
        ];
    }

    private function getTournamentProgress(): ?array
    {
        $eventID = session('eventID');
        if (!$eventID) return null;

        $event = DB::table('events')->where('id', $eventID)->first();
        if (!$event) return null;

        $total  = DB::table('games')->where('event_id', $eventID)->count();
        $scored = DB::table('games')->where('event_id', $eventID)
                    ->whereNotNull('home_team_score')->count();
        // Use Vilnius timezone (auto-adjusts for DST: UTC+2 summer, UTC+1 winter)
        $todayStart = Carbon::today('Europe/Vilnius')->utc();
        $todayEnd   = Carbon::today('Europe/Vilnius')->endOfDay()->utc();

        $today  = DB::table('games')->where('event_id', $eventID)
                    ->whereNull('home_team_score')
                    ->whereBetween('game_date', [$todayStart, $todayEnd])->count();

        return [
            'event_name'   => $event->event,
            'total_games'  => $total,
            'scored_games' => $scored,
            'today_games'  => $today,
            'pct'          => $total > 0 ? (int) round($scored / $total * 100) : 0,
        ];
    }

}
