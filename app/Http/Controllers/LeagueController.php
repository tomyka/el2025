<?php
namespace App\Http\Controllers;

use App\Models\League;
use App\Models\LeagueMember;
use App\Models\LeagueInvite;
use App\Models\User;
use Illuminate\Http\Request;

class LeagueController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        $userId = session('userID');

        $myLeagues = LeagueMember::where('user_id', $userId)
            ->with(['league', 'league.members.user', 'league.pendingInvites.invitedUser'])
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
            'use_league_odds'    => (bool) $request->input('use_league_odds', false),
            'reward_description' => $request->input('reward_description'),
        ]);

        LeagueMember::firstOrCreate(
            ['league_id' => $league->id, 'user_id' => $userId],
            ['is_admin' => true, 'is_guest' => false, 'active' => false]
        );

        return redirect()->route('leagues.index')->with('info', 'Lyga sukurta');
    }

    public function update(Request $request): \Illuminate\Http\RedirectResponse
    {
        $leagueId = (int) $request->input('leagueID');
        $userId   = session('userID');

        LeagueMember::where('league_id', $leagueId)
            ->where('user_id', $userId)
            ->where('is_admin', true)
            ->firstOrFail();

        $request->validate([
            'name'         => 'required|string|max:100',
            'description'  => 'nullable|string|max:500',
            'base_fee'     => 'nullable|integer|min:0',
            'penalty_step' => 'nullable|integer|min:0',
        ]);

        League::findOrFail($leagueId)->update([
            'name'            => $request->input('name'),
            'description'     => $request->input('description'),
            'base_fee'        => $request->input('base_fee'),
            'penalty_step'    => $request->input('penalty_step'),
            'use_league_odds' => (bool) $request->input('use_league_odds', false),
        ]);

        return redirect()->route('leagues.index')->with('info', 'Lyga atnaujinta');
    }

    public function invite(Request $request): \Illuminate\Http\RedirectResponse
    {
        $leagueId = (int) $request->input('leagueID');
        $userId   = session('userID');

        LeagueMember::where('league_id', $leagueId)
            ->where('user_id', $userId)
            ->where('is_admin', true)
            ->firstOrFail();

        $inviteeId = (int) $request->input('invitedUserID');

        $alreadyMember  = LeagueMember::where('league_id', $leagueId)->where('user_id', $inviteeId)->exists();
        $alreadyInvited = LeagueInvite::where('league_id', $leagueId)->where('invited_user_id', $inviteeId)->where('status', 'pending')->exists();

        if ($alreadyMember || $alreadyInvited) {
            return redirect()->back()->with('error', 'Vartotojas jau narys arba pakviestas');
        }

        LeagueInvite::create([
            'league_id'       => $leagueId,
            'invited_user_id' => $inviteeId,
            'invited_by_id'   => $userId,
            'status'          => 'pending',
        ]);

        return redirect()->back()->with('info', 'Kvietimas išsiųstas');
    }

    public function acceptInvite(Request $request): \Illuminate\Http\RedirectResponse
    {
        $userId = session('userID');
        $invite = LeagueInvite::where('id', $request->input('inviteID'))
            ->where('invited_user_id', $userId)
            ->where('status', 'pending')
            ->firstOrFail();

        LeagueMember::firstOrCreate(
            ['league_id' => $invite->league_id, 'user_id' => $userId],
            ['is_admin' => false, 'is_guest' => false, 'active' => false]
        );

        $invite->delete();

        return redirect()->route('leagues.index')->with('info', 'Prisijungta prie lygos');
    }

    public function declineInvite(Request $request): \Illuminate\Http\RedirectResponse
    {
        $userId = session('userID');
        $invite = LeagueInvite::where('id', $request->input('inviteID'))
            ->where('invited_user_id', $userId)
            ->firstOrFail();

        $invite->delete();

        return redirect()->route('leagues.index')->with('info', 'Kvietimas atmestas');
    }

    public function switchLeague(Request $request): \Illuminate\Http\RedirectResponse
    {
        $userId      = session('userID');
        $newLeagueId = (int) $request->input('leagueID');

        $newMembership = LeagueMember::where('user_id', $userId)
            ->where('league_id', $newLeagueId)
            ->firstOrFail();

        LeagueMember::where('user_id', $userId)->update(['active' => false]);
        $newMembership->update(['active' => true]);

        session(['leagueID' => $newLeagueId]);
        $newMembership->load('league');
        if ($newMembership->league) {
            session(['fee' => $newMembership->league->base_fee]);
        }

        return redirect()->back()->with('info', 'Lyga pakeista');
    }

    public function searchUsers(Request $request): \Illuminate\Http\JsonResponse
    {
        $userId   = session('userID');
        $leagueId = (int) $request->input('leagueID');
        $query    = $request->input('query', '');

        LeagueMember::where('league_id', $leagueId)
            ->where('user_id', $userId)
            ->where('is_admin', true)
            ->firstOrFail();

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $existingMemberIds  = LeagueMember::where('league_id', $leagueId)->pluck('user_id');
        $pendingInviteeIds  = LeagueInvite::where('league_id', $leagueId)->where('status', 'pending')->pluck('invited_user_id');
        $excludeIds         = $existingMemberIds->merge($pendingInviteeIds)->unique();

        $users = User::where(function ($q) use ($query) {
                $q->where('name',     'like', "%{$query}%")
                  ->orWhere('surname',  'like', "%{$query}%")
                  ->orWhere('username', 'like', "%{$query}%");
            })
            ->whereNotIn('id', $excludeIds)
            ->select('id', 'username', 'name', 'surname')
            ->limit(10)
            ->get();

        return response()->json($users);
    }

    public function toggleOdds(Request $request): \Illuminate\Http\RedirectResponse
    {
        $userId   = session('userID');
        $leagueId = (int) $request->input('leagueID');

        LeagueMember::where('user_id', $userId)
            ->where('league_id', $leagueId)
            ->where('is_admin', true)
            ->firstOrFail();

        $league = League::findOrFail($leagueId);
        $league->update(['use_league_odds' => (bool) $request->input('use_league_odds', false)]);

        return redirect()->route('leagues.index')->with('info', 'Lygos koeficientai atnaujinti');
    }

    public function deleteLeague(Request $request): \Illuminate\Http\RedirectResponse
    {
        $userId   = session('userID');
        $leagueId = (int) $request->input('leagueID');

        $league = League::findOrFail($leagueId);

        if ($league->owner_id !== $userId) {
            abort(403);
        }

        if ($league->is_public) {
            return redirect()->back()->with('error', 'Viešos lygos ištrinti negalima');
        }

        $publicLeague    = League::where('is_public', true)->first();
        $activeMemberIds = LeagueMember::where('league_id', $leagueId)
            ->where('active', true)
            ->pluck('user_id');

        if ($publicLeague && $activeMemberIds->isNotEmpty()) {
            LeagueMember::whereIn('user_id', $activeMemberIds)
                ->where('league_id', $publicLeague->id)
                ->update(['active' => true]);

            if ($activeMemberIds->contains($userId)) {
                session(['leagueID' => $publicLeague->id]);
            }
        }

        \DB::table('messages')->where('league_id', $leagueId)->update(['league_id' => null]);

        $league->delete();

        return redirect()->route('leagues.index')->with('info', 'Lyga ištrinta');
    }

    public function leaveLeague(Request $request): \Illuminate\Http\RedirectResponse
    {
        $userId   = session('userID');
        $leagueId = (int) $request->input('leagueID');

        $league = League::findOrFail($leagueId);

        if ($league->is_public) {
            abort(403, 'Negalima palikti viešos lygos');
        }

        if ($league->owner_id === $userId) {
            return redirect()->back()->with('error', 'Perduokite lygos valdymą kitam nariui prieš išeidami');
        }

        $membership = LeagueMember::where('user_id', $userId)
            ->where('league_id', $leagueId)
            ->firstOrFail();

        $wasActive = (bool) $membership->active;
        $membership->delete();

        if ($wasActive) {
            $publicLeague     = League::where('is_public', true)->firstOrFail();
            $publicMembership = LeagueMember::where('user_id', $userId)
                ->where('league_id', $publicLeague->id)
                ->firstOrFail();
            $publicMembership->update(['active' => true]);
            session(['leagueID' => $publicLeague->id]);
        }

        return redirect()->route('leagues.index')->with('info', 'Palikote lygą');
    }
}
