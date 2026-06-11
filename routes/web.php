<?php

use App\Http\Controllers\MainController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PredictionResultController;
use App\Http\Controllers\PredictionStandingController;
use App\Http\Controllers\PredictionSurvivalController;
use App\Http\Controllers\UserSettingController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\RulesController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ChartController;
use App\Http\Controllers\PointStandingController;
use App\Http\Controllers\LeagueController;
use Illuminate\Support\Facades\Route;

/*Original routes start*/
/*Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
*/

Route::middleware('auth')->group(function () {
    Route::get('/profile', [UserProfileController::class, 'getUserProfile'])->name('profile.edit');
    Route::patch('/profile', [UserProfileController::class, 'updateUserProfile'])->name('profile.update');
    Route::delete('/profile', [UserProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/dashboard', function () {
    return redirect()->route('main');
})->name('dashboard');
/*Original routes end*/


Route::get('/', [MainController::class,  'loadApp'])->name('/');
Route::get('main', [MainController::class,  'loadApp'])->name('main');
Route::get('admin', function () {return redirect()->route('admin.index');})->name('admin');

Route::get('rules', [RulesController::class,'getRulesDetails'])->name('rules');
Route::get('help', function () {return view('help');})->name('help');
Route::get('privacy', function () {return view('privacy');})->name('privacy');
Route::get('charity', function () {return view('charity');})->name('charity');
Route::get('support', function () {return view('support');})->name('support');
Route::get('sponsors', function () {return view('sponsors');})->name('sponsors');

Route::middleware('auth')->group(function () {
    Route::get('userProfile', [UserProfileController::class, 'getUserProfile'])->name('userProfile');
    Route::post('userProfile', [UserProfileController::class, 'updateUserProfile']);

    Route::get('users', [UserController::class, 'getAllUsers'])->name('users');

    // @deprecated — settings merged into profile page; routes kept to avoid 404 on stale bookmarks
    Route::get('userSettings', [UserSettingController::class, 'getUserSettings'])->name('userSettings');
    Route::post('userSettings', [UserSettingController::class, 'updateUserSettings']);

    Route::group(['prefix' => 'prediction'], function () {
        Route::get('results', [PredictionResultController::class, 'getPredictionResultsUser'])->name('prediction.results');
        Route::post('results', [PredictionResultController::class, 'updatePredictionResultUser']);

        Route::get('standings', [PredictionStandingController::class, 'getPredictionStandingsUser'])->name('prediction.standings');
        Route::post('standings', [PredictionStandingController::class, 'updatePredictionStandingsUser']);

        Route::get('predictionSurvival', [PredictionSurvivalController::class, 'getPredictionSurvivalUser'])->name('prediction.survival');
        Route::post('predictionSurvival', [PredictionSurvivalController::class, 'updatePredictionSurvivalUser']);
    });

    Route::get('/leagues', [LeagueController::class, 'index'])->name('leagues.index');
    Route::post('/leagues/create', [LeagueController::class, 'create'])->name('leagues.create');
    Route::post('/leagues/invite',  [LeagueController::class, 'invite'])->name('leagues.invite');
    Route::post('/leagues/accept',  [LeagueController::class, 'acceptInvite'])->name('leagues.accept');
    Route::post('/leagues/decline', [LeagueController::class, 'declineInvite'])->name('leagues.decline');
    Route::post('/leagues/switch', [LeagueController::class, 'switchLeague'])->name('leagues.switch');
    Route::post('/leagues/leave',  [LeagueController::class, 'leaveLeague'])->name('leagues.leave');
    Route::get('/leagues/searchUsers', [LeagueController::class, 'searchUsers'])->name('leagues.searchUsers');
    Route::post('/leagues/update',     [LeagueController::class, 'update'])->name('leagues.update');
    Route::post('/leagues/delete',     [LeagueController::class, 'deleteLeague'])->name('leagues.delete');
    Route::post('/leagues/toggleOdds', [LeagueController::class, 'toggleOdds'])->name('leagues.toggleOdds');
});

// Admin (level 1+): dashboard, games, results
Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'admin']], function () {

    Route::get('index', fn() => view('admin.index'))->name('admin.index');

    Route::get('games', [GameController::class,'getGameAll'])->name('admin.games');
    Route::post('updateGame', [GameController::class,'updateGame'])->name('admin.updateGame');
    Route::post('deleteGame', [GameController::class,'deleteGame'])->name('admin.deleteGame');
    Route::post('insertGame', [GameController::class,'insertGame'])->name('admin.insertGame');

    Route::get('results', [ResultController::class,'getResultsCurrentRound'])->name('admin.results');
    Route::get('resultsAll', [ResultController::class,'getResultsAll'])->name('admin.resultsAll');
    Route::post('updateResult', [ResultController::class,'updateResult'])->name('admin.updateResult');

    Route::get('leagues', [LeagueController::class, 'adminIndex'])->name('admin.leagues');

});

// SuperAdmin (level 9): everything else
Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'superadmin']], function () {

    Route::get('users', [UserController::class,'getAllUsersFull'])->name('admin.users');
    Route::post('updateUser', [UserController::class,'updateUser'])->name('admin.updateUser');
    Route::post('deleteUser', [UserController::class,'deleteUser'])->middleware('level9admin')->name('admin.deleteUser');

    Route::get('teams',[TeamController::class,'getTeam'])->name('admin.teams');
    Route::get('teaminsert', [TeamController::class,'getTeam'])->name('admin.teaminsert');
    Route::post('teams', [TeamController::class,'updateTeams'])->name('admin.teams');
    Route::post('teaminsert', [TeamController::class,'insertTeams'])->name('admin.teamsinsert');
    Route::post('updateTeamDetails', [TeamController::class,'updateTeamDetails'])->name('admin.updateTeamDetails');

    Route::get('events', [EventController::class,'getEvent'])->name('admin.events');
    Route::post('events', [EventController::class,'updateEvent'])->name('admin.events');
    Route::post('eventInsert', [EventController::class,'insertEvent'])->name('admin.eventInsert');

    Route::get('messages',[MessageController::class,'getMessageAll'])->name('admin.messages');
    Route::post('messages', [MessageController::class,'updateMessage'])->name('admin.messages');
    Route::post('messageInsert', [MessageController::class,'insertMessage'])->name('admin.messageInsert');

    Route::get('updateStandingPoints', [PointStandingController::class,'updateStandingPoints'])->name('admin.updateStandingPoints');

    Route::post('leagues/delete', [LeagueController::class, 'adminDelete'])->middleware('level9admin')->name('admin.leagues.delete');

    // @deprecated — settings removed from admin panel; routes kept to avoid 404 on stale bookmarks
    Route::get('settings', [SettingController::class,'getSettingAll'])->name('admin.settings');
    Route::post('settings', [SettingController::class,'updateSetting']);
});

Route::group(['prefix' => 'summary'],function(){

    Route::get('prediction/results', [PredictionResultController::class,'getPredictionResultSummary'])->name('summary.prediction.results');
    Route::get('prediction/standings', [PredictionStandingController::class,'getPredictionStandingSummary'])->name('summary.prediction.standings');
    Route::get('predictionSurvivals', [PredictionSurvivalController::class,'getPredictionSurvivalSummary'])->name('summary.prediction.survivals');
    Route::get('chart', [ChartController::class,'getChartData'])->name('summary.chart');
});

require __DIR__.'/auth.php';
