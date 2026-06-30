<?php
namespace App\Http\Controllers;

use App\Models\League;
use App\Models\LeagueMember;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
                ->keyBy(fn($m) => $m->league->tournament_id);
        }

        return view('tournaments.hub', compact('tournaments', 'myLeaguesByTournament'));
    }

    public function enter(string $slug): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $tournament  = Tournament::where('slug', $slug)->firstOrFail();
        $membership  = LeagueMember::where('user_id', Auth::id())
            ->where('active', true)
            ->whereHas('league', fn($q) => $q->where('tournament_id', $tournament->id))
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
            'league', fn($q) => $q->where('tournament_id', $tournament->id)
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
        return view('admin.tournaments.form', ['tournament' => new Tournament()]);
    }

    public function adminStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'slug'          => 'required|string|max:100|unique:tournaments,slug|regex:/^[a-z0-9-]+$/',
            'sport'         => 'required|string|max:50',
            'status'        => 'required|in:upcoming,active,finished',
            'start_date'    => 'nullable|date',
            'end_date'      => 'nullable|date|after_or_equal:start_date',
            'description'   => 'nullable|string|max:1000',
            'cover_image'   => 'nullable|string|max:255',
            'is_public'     => 'boolean',
            'survival_game' => 'boolean',
        ]);
        $data['is_public']     = $request->boolean('is_public');
        $data['survival_game'] = $request->boolean('survival_game');

        Tournament::create($data);
        return redirect()->route('admin.tournaments')->with('info', 'Turnyras sukurtas.');
    }

    public function adminEdit(Tournament $tournament): View
    {
        return view('admin.tournaments.form', compact('tournament'));
    }

    public function adminUpdate(Request $request, Tournament $tournament): RedirectResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'slug'          => 'required|string|max:100|regex:/^[a-z0-9-]+$/|unique:tournaments,slug,'.$tournament->id,
            'sport'         => 'required|string|max:50',
            'status'        => 'required|in:upcoming,active,finished',
            'start_date'    => 'nullable|date',
            'end_date'      => 'nullable|date|after_or_equal:start_date',
            'description'   => 'nullable|string|max:1000',
            'cover_image'   => 'nullable|string|max:255',
            'is_public'     => 'boolean',
            'survival_game' => 'boolean',
        ]);
        $data['is_public']     = $request->boolean('is_public');
        $data['survival_game'] = $request->boolean('survival_game');

        $tournament->update($data);
        return redirect()->route('admin.tournaments')->with('info', 'Turnyras atnaujintas.');
    }

    public function adminEnterContext(Tournament $tournament): RedirectResponse
    {
        Session::put('tournamentID', $tournament->id);
        return redirect()->route('admin.index')->with('info', 'Turnyro kontekstas: '.$tournament->name);
    }
}
