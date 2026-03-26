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
use App\Http\Controllers\GroupController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\UserGroupController;
use App\Http\Controllers\RulesController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ChartController;
use App\Http\Controllers\PointStandingController;
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
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
/*Original routes end*/


Route::get('/', [MainController::class,  'loadApp'])->name('/');
Route::get('main', [MainController::class,  'loadApp'])->name('main');
Route::get('admin', function () {return redirect()->route('admin.index');})->name('admin');

Route::get('userProfile', [UserProfileController::class, 'getUserProfile'])->name('userProfile');
Route::post('userProfile', [UserProfileController::class,'updateUserProfile'])->name('userProfile');

Route::get('users', [UserController::class, 'getAllUsers'])->name('users');
Route::get('rules', [RulesController::class,'getRulesDetails'])->name('rules');
Route::get('help', function () {return view('help');})->name('help');
Route::get('charity', function () {return view('charity');})->name('charity');
Route::get('support', function () {return view('support');})->name('support');
Route::get('sponsors', function () {return view('sponsors');})->name('sponsors');

Route::get('userSettings', [UserSettingController::class,'getUserSettings'])->name('userSettings');
Route::post('userSettings', [UserSettingController::class,'updateUserSettings'])->name('userSettings');

Route::get('userGroup'     , [UserGroupController::class,'getUserGroup'])->name('userGroup');
Route::post('updateUserGroup' , [UserGroupController::class,'updateUserGroup'])->name('updateUserGroup');
Route::post('deleteUserGroup' , [UserGroupController::class,'deleteUserGroup'])->name('deleteUserGroup');
Route::post('insertUserGroup' , [UserGroupController::class,'insertUserGroup'])->name('insertUserGroup');

Route::group(['prefix' => 'prediction'],function(){

    Route::get('results', [PredictionResultController::class,'getPredictionResultsUser'])->name('prediction.results');
    Route::post('results', [PredictionResultController::class,'updatePredictionResultUser'])->name('prediction.results');

    Route::get('standings', [PredictionStandingController::class,'getPredictionStandingsUser'])->name('prediction.standings');
    Route::post('standings', [PredictionStandingController::class,'updatePredictionStandingsUser'])->name('prediction.standings');

    Route::get('predictionSurvival', [PredictionSurvivalController::class,'getPredictionSurvivalUser'])->name('prediction.survival');
    Route::post('predictionSurvival', [PredictionSurvivalController::class,'updatePredictionSurvivalUser'])->name('prediction.survival');
});

Route:: group(['prefix'=>'admin'], function(){

    Route::get('index', [ResultController::class,'getResultsCurrentRound'])->name('admin.index');

    Route::get('users', [UserController::class,'getAllUsersFull'])->name('admin.users');
    Route::post('updateUser', [UserController::class,'updateUser'])->name('admin.updateUser');
    Route::post('deleteUser', [UserController::class,'deleteUser'])->name('admin.deleteUser');

    Route::get('userGroups', [UserGroupController::class,'getUserGroupAll'])->name('admin.userGroups');

    Route::get('teams', [TeamController::class,'getTeam'])->name('admin.teams');
    Route::get('teaminsert', [TeamController::class,'getTeam'])->name('admin.teaminsert');
    Route::post('teams', [TeamController::class,'updateTeams'])->name('admin.teams');
    Route::post('teaminsert', [TeamController::class,'insertTeams'])->name('admin.teamsinsert');

    Route::get('games', [GameController::class,'getGameAll'])->name('admin.games');
    Route::post('updateGame', [GameController::class,'updateGame'])->name('admin.updateGame');
    Route::post('deleteGame', [GameController::class,'deleteGame'])->name('admin.deleteGame');
    Route::post('insertGame', [GameController::class,'insertGame'])->name('admin.insertGame');

    Route::get('results', [ResultController::class,'getResultsCurrentRound'])->name('admin.results');
    Route::get('resultsAll', [ResultController::class,'getResultsAll'])->name('admin.resultsAll');
    Route::post('updateResult', [ResultController::class,'updateResult'])->name('admin.updateResult');

    Route::get('events', [EventController::class,'getEvent'])->name('admin.events');
    Route::post('events', [EventController::class,'updateEvent'])->name('admin.events');
    Route::post('eventInsert', [EventController::class,'insertEvent'])->name('admin.eventInsert');

    Route::get('groups', [GroupController::class,'getGroup'])->name('admin.groups');
    Route::post('groups', [GroupController::class,'updateGroup'])->name('admin.groups');
    Route::post('groupInsert', [GroupController::class,'insertGroup'])->name('admin.groupInsert');

    Route::get('messages', [MessageController::class,'getMessageAll'])->name('admin.messages');
    Route::post('messages', [MessageController::class,'updateMessage'])->name('admin.messages');
    Route::post('messageInsert', [MessageController::class,'insertMessage'])->name('admin.messageInsert');

    Route::get('settings', [SettingController::class,'getSettingAll'])->name('admin.settings');
    Route::post('settings', [SettingController::class,'updateSetting'])->name('admin.settings');

    Route::get('updateStandingPoints', [PointStandingController::class,'updateStandingPoints'])->name('admin.updateStandingPoints');
});

Route::group(['prefix' => 'summary'],function(){

    Route::get('history', [PredictionResultController::class,'getPredictionResultHistory'])->name('summary.history');
    Route::get('prediction/results', [PredictionResultController::class,'getPredictionResultSummary'])->name('summary.prediction.results');
    Route::get('prediction/standings', [PredictionStandingController::class,'getPredictionStandingSummary'])->name('summary.prediction.standings');
    Route::get('predictionSurvivals', [PredictionSurvivalController::class,'getPredictionSurvivalSummary'])->name('summary.prediction.survivals');
    Route::get('chart', [ChartController::class,'getChartData'])->name('summary.chart');
});

require __DIR__.'/auth.php';
