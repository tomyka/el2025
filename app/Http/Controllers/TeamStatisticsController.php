<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class TeamStatisticsController extends Controller
{
    public function getTeamStatistics($teamID): ?object
    {
        $games = DB::table('games')
            ->where(function ($q) use ($teamID) {
                $q->where('home_team_id', $teamID)->orWhere('away_team_id', $teamID);
            })
            ->whereNotNull('home_team_score')
            ->whereNotNull('away_team_score')
            ->select('home_team_id', 'home_team_score', 'away_team_score')
            ->get();

        if ($games->isEmpty()) {
            return null;
        }

        return $this->aggregateStats((int) $teamID, $games);
    }

    public function prepareTeamStatistics($predictionResults): array
    {
        if (empty($predictionResults)) {
            return [];
        }

        // Collect distinct team IDs to fetch all stats in one query
        $teamIds = [];
        foreach ($predictionResults as $pr) {
            $teamIds[(int) $pr->home_team_id] = true;
            $teamIds[(int) $pr->away_team_id] = true;
        }
        $teamIds = array_keys($teamIds);

        $games = DB::table('games')
            ->where(function ($q) use ($teamIds) {
                $q->whereIn('home_team_id', $teamIds)->orWhereIn('away_team_id', $teamIds);
            })
            ->whereNotNull('home_team_score')
            ->whereNotNull('away_team_score')
            ->select('home_team_id', 'away_team_id', 'home_team_score', 'away_team_score')
            ->get();

        // Build per-team stats from the single result set
        $statsByTeam = [];
        foreach ($teamIds as $tid) {
            $statsByTeam[$tid] = collect();
        }
        foreach ($games as $game) {
            $homeId = (int) $game->home_team_id;
            $awayId = (int) $game->away_team_id;
            if (isset($statsByTeam[$homeId])) {
                $statsByTeam[$homeId]->push($game);
            }
            if (isset($statsByTeam[$awayId])) {
                $statsByTeam[$awayId]->push($game);
            }
        }

        $statsObjects = [];
        foreach ($teamIds as $tid) {
            $statsObjects[$tid] = $statsByTeam[$tid]->isEmpty()
                ? null
                : $this->aggregateStats($tid, $statsByTeam[$tid]);
        }

        $result = [];
        foreach ($predictionResults as $predictionResult) {
            $result[] = [
                'gameDetails' => $predictionResult,
                'homeTeamStats' => $statsObjects[(int) $predictionResult->home_team_id] ?? null,
                'awayTeamStats' => $statsObjects[(int) $predictionResult->away_team_id] ?? null,
            ];
        }

        return $result;
    }

    private function aggregateStats(int $teamID, iterable $games): object
    {
        $stats = ['gameCount' => 0, 'won' => 0, 'lost' => 0, 'pointsScored' => 0.0, 'pointsAllowed' => 0.0];
        foreach ($games as $game) {
            $isHome = (int) $game->home_team_id === $teamID;
            $scored = $isHome ? (int) $game->home_team_score : (int) $game->away_team_score;
            $conceded = $isHome ? (int) $game->away_team_score : (int) $game->home_team_score;
            $stats['gameCount']++;
            $stats['pointsScored'] += $scored;
            $stats['pointsAllowed'] += $conceded;
            if ($scored > $conceded) {
                $stats['won']++;
            }
            if ($scored < $conceded) {
                $stats['lost']++;
            }
        }

        return (object) $stats;
    }
}
