<?php
namespace App\Http\Controllers;

use App\Models\League;
use App\Models\LeagueMember;
use App\Models\LeagueInvite;
use Illuminate\Http\Request;

class LeagueController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        $userId = session('userID');

        $myLeagues = LeagueMember::where('user_id', $userId)
            ->with('league')
            ->get();

        $pendingInvites = LeagueInvite::where('invited_user_id', $userId)
            ->where('status', 'pending')
            ->with(['league', 'invitedBy'])
            ->get();

        return view('leagues.index', compact('myLeagues', 'pendingInvites'));
    }

    public function create(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'name'               => 'required|string|max:100',
            'description'        => 'nullable|string|max:500',
            'base_fee'           => 'nullable|integer|min:0',
            'penalty_step'       => 'nullable|integer|min:0',
            'reward_description' => 'nullable|string|max:500',
        ]);

        $userId = session('userID');

        $league = League::create([
            'name'               => $request->input('name'),
            'description'        => $request->input('description'),
            'is_public'          => false,
            'owner_id'           => $userId,
            'base_fee'           => $request->input('base_fee'),
            'penalty_step'       => $request->input('penalty_step'),
            'use_league_odds'    => false,
            'reward_description' => $request->input('reward_description'),
        ]);

        LeagueMember::firstOrCreate(
            ['league_id' => $league->id, 'user_id' => $userId],
            ['is_admin' => true, 'is_guest' => false, 'active' => false]
        );

        return redirect()->route('leagues.index')->with('info', 'Liga sukurta');
    }
}
