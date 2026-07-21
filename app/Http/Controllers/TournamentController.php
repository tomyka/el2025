<?php

namespace App\Http\Controllers;

use App\Models\LeagueMember;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class TournamentController extends Controller
{
    public function hub(): View
    {
        $tournaments = Tournament::orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'upcoming' THEN 1 ELSE 2 END")
            ->orderBy('start_date')
            ->get();

        $myLeaguesByTournament = collect();
        if (Auth::check()) {
            $myLeaguesByTournament = LeagueMember::where('user_id', Auth::id())
                ->where('active', true)
                ->with('league')
                ->get()
                ->keyBy(fn ($m) => $m->league->tournament_id);
        }

        $now = now()->toDateTimeString();
        $tData = [];
        foreach ($tournaments as $t) {
            $tid = $t->id;

            $gameCount = DB::table('games')
                ->join('events', 'games.event_id', '=', 'events.id')
                ->where('events.tournament_id', $tid)
                ->count();
            $unscoredCount = DB::table('games')
                ->join('events', 'games.event_id', '=', 'events.id')
                ->where('events.tournament_id', $tid)
                ->whereNull('games.home_team_score')
                ->count();

            $tData[$tid] = [
                // All games played, even if an admin hasn't flipped the tournament's
                // status to "finished" yet.
                'allGamesFinished' => $gameCount > 0 && $unscoredCount === 0,

                'participantCount' => LeagueMember::whereHas('league', fn ($q) => $q->where('tournament_id', $tid))
                    ->where('is_guest', false)->where('active', true)->distinct()->count('user_id'),

                'predictionCount' => DB::table('point_results as pr')
                    ->join('games as g', 'pr.game_id', '=', 'g.id')
                    ->join('events as e', 'g.event_id', '=', 'e.id')
                    ->where('e.tournament_id', $tid)
                    ->count(),

                'leaderboard' => DB::select('
                    SELECT u.username,
                           ROUND(SUM(IFNULL(pr.full_points,0) + IFNULL(pr.streak_bonus,0)),1) AS total_points
                    FROM users u
                    JOIN user_settings us ON us.user_id = u.id
                    JOIN point_results pr ON pr.user_id = u.id
                    JOIN games g ON pr.game_id = g.id
                    JOIN events e ON g.event_id = e.id
                    WHERE us.active = 1 AND e.tournament_id = ?
                    GROUP BY u.id, u.username
                    HAVING total_points > 0
                    ORDER BY total_points DESC
                    LIMIT 5
                ', [$tid]),

                'medalRows' => DB::select('
                    SELECT tm.team,
                        SUM(CASE WHEN ps.final = 1 THEN 1 ELSE 0 END) AS firstPlacePrediction,
                        SUM(CASE WHEN ps.final = 2 THEN 1 ELSE 0 END) AS secondPlacePrediction,
                        SUM(CASE WHEN ps.final = 3 THEN 1 ELSE 0 END) AS thirdPlacePrediction,
                        SUM(CASE WHEN ps.final = 4 THEN 1 ELSE 0 END) AS fourthPlacePrediction
                    FROM prediction_standings ps
                    JOIN teams tm ON ps.team_id = tm.id
                    JOIN user_settings us ON ps.user_id = us.user_id
                    WHERE ps.final IS NOT NULL AND us.active = 1 AND tm.tournament_id = ?
                    GROUP BY tm.team
                    ORDER BY firstPlacePrediction DESC, secondPlacePrediction DESC, thirdPlacePrediction DESC
                ', [$tid]),

                'upcomingGames' => DB::select('
                    SELECT g.game_date, ht.team AS home_team, at.team AS away_team
                    FROM games g
                    JOIN events e ON g.event_id = e.id
                    JOIN teams ht ON g.home_team_id = ht.id
                    JOIN teams at ON g.away_team_id = at.id
                    WHERE e.tournament_id = ? AND g.home_team_score IS NULL AND g.game_date >= ?
                    ORDER BY g.game_date
                    LIMIT 3
                ', [$tid, $now]),
            ];
        }

        return view('tournaments.hub', compact('tournaments', 'myLeaguesByTournament', 'tData'));
    }

    public function enter(string $slug): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $tournament = Tournament::where('slug', $slug)->firstOrFail();
        $membership = LeagueMember::where('user_id', Auth::id())
            ->where('active', true)
            ->whereHas('league', fn ($q) => $q->where('tournament_id', $tournament->id))
            ->with('league')
            ->first();

        Session::put('tournamentID', $tournament->id);

        if ($membership) {
            Session::put('leagueID', $membership->league_id);

            return redirect()->route('main');
        }

        return redirect()->route('tournament.show', $slug);
    }

    public function exit(): RedirectResponse
    {
        Session::forget(['tournamentID', 'leagueID']);

        return redirect()->route('tournaments.hub');
    }

    public function show(string $slug): View
    {
        $tournament = Tournament::where('slug', $slug)->firstOrFail();

        $participantCount = LeagueMember::whereHas(
            'league', fn ($q) => $q->where('tournament_id', $tournament->id)
        )->where('is_guest', false)->distinct()->count('user_id');

        return view('tournaments.show', compact('tournament', 'participantCount'));
    }

    // ── Admin ──────────────────────────────────────────────────────────────

    public function adminIndex(): View
    {
        $tournaments = Tournament::orderByDesc('created_at')->get();

        return view('admin.tournaments.index', compact('tournaments'));
    }

    public function adminCreate(): View
    {
        return view('admin.tournaments.form', ['tournament' => new Tournament]);
    }

    public function adminStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|unique:tournaments,slug|regex:/^[a-z0-9-]+$/',
            'sport' => 'required|string|max:50',
            'status' => 'required|in:upcoming,active,finished',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string|max:1000',
            'cover_image' => 'nullable|string|max:255',
            'is_public' => 'boolean',
            'survival_game' => 'boolean',
        ]);
        $data['is_public'] = $request->boolean('is_public');
        $data['survival_game'] = $request->boolean('survival_game');

        Tournament::create($data);

        return redirect()->route('admin.tournaments')->with('info', __('Turnyras sukurtas.'));
    }

    public function adminEdit(Tournament $tournament): View
    {
        return view('admin.tournaments.form', compact('tournament'));
    }

    public function adminUpdate(Request $request, Tournament $tournament): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|regex:/^[a-z0-9-]+$/|unique:tournaments,slug,'.$tournament->id,
            'sport' => 'required|string|max:50',
            'status' => 'required|in:upcoming,active,finished',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string|max:1000',
            'cover_image' => 'nullable|string|max:255',
            'is_public' => 'boolean',
            'survival_game' => 'boolean',
        ]);
        $data['is_public'] = $request->boolean('is_public');
        $data['survival_game'] = $request->boolean('survival_game');

        $tournament->update($data);

        return redirect()->route('admin.tournaments')->with('info', __('Turnyras atnaujintas.'));
    }

    public function adminEnterContext(Tournament $tournament): RedirectResponse
    {
        Session::put('tournamentID', $tournament->id);

        return redirect()->route('admin.index')->with('info', __('Turnyro kontekstas: :name', ['name' => $tournament->name]));
    }
}
