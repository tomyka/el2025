<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(): View
    {
        $userFilter = request()->integer('user', 0) ?: null;

        $loginsQuery = DB::table('audit_logins')
            ->leftJoin('users', 'audit_logins.user_id', '=', 'users.id')
            ->select('audit_logins.*', 'users.username')
            ->orderBy('audit_logins.created_at', 'desc');

        if ($userFilter) {
            $loginsQuery->where('audit_logins.user_id', (string) $userFilter);
        }

        $logins = $loginsQuery->paginate(25, ['*'], 'logins_page')
            ->appends(array_filter(['user' => $userFilter]));

        $predictionsQuery = DB::table('audit_prediction_games')
            ->leftJoin('users', 'audit_prediction_games.user_id', '=', 'users.id')
            ->leftJoin('games', 'audit_prediction_games.game_id', '=', 'games.id')
            ->leftJoin('teams as ht', 'games.home_team_id', '=', 'ht.id')
            ->leftJoin('teams as at', 'games.away_team_id', '=', 'at.id')
            ->select(
                'audit_prediction_games.*',
                'users.username',
                'ht.team as home_team',
                'at.team as away_team'
            )
            ->orderBy('audit_prediction_games.created_at', 'desc');

        if ($userFilter) {
            $predictionsQuery->where('audit_prediction_games.user_id', $userFilter);
        }

        $predictions = $predictionsQuery->paginate(25, ['*'], 'predictions_page')
            ->appends(array_filter(['user' => $userFilter]));

        $users = DB::table('users')->orderBy('username')->get(['id', 'username']);

        return view('admin.audit', compact('logins', 'predictions', 'users', 'userFilter'));
    }
}
