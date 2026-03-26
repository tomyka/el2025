<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('group');
            $table->string('group_description')->nullable();
            $table->smallInteger('fee')->nullable();
            $table->string('fee_description')->nullable();
            $table->decimal('reward_ratio')->nullable();
            $table->string('reward_description')->nullable();
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('team');
            $table->string('link')->nullable();
            $table->string('group_name')->nullable();
            $table->tinyInteger('group_position')->nullable();
            $table->tinyInteger('last16')->nullable();
            $table->tinyInteger('quarterfinal')->nullable();
            $table->tinyInteger('semifinal')->nullable();
            $table->tinyInteger('final')->nullable();
            $table->timestamps();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('event');
            $table->smallInteger('event_day');
            $table->tinyInteger('event_survival');
            $table->boolean('active');
            $table->tinyInteger('rate');
            $table->timestamps();
        });


        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->dateTime('game_date');
            $table->tinyInteger('event_id')->constrained('events');
            $table->tinyInteger('home_team_id')->constrained('teams');
            $table->tinyInteger('away_team_id')->constrained('teams');
            $table->smallInteger('home_team_score')->nullable();
            $table->smallInteger('away_team_score')->nullable();
            $table->tinyInteger('game_winner_id')->nullable();

            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups');
            $table->string('message');
            $table->tinyInteger('active');

            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting');
            $table->smallInteger('value');
            $table->timestamps();
        });


        Schema::create('user_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('group_id')->constrained('groups');
            $table->boolean('active')->nullable();
            $table->boolean('guest')->nullable();
            $table->smallInteger('fee')->nullable();
            $table->timestamps();
        });

        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->tinyInteger('admin');
            $table->smallInteger('result_amount')->nullable();
            $table->smallInteger('time_zone')->nullable();
            $table->timestamps();
        });

        Schema::create('prediction_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('game_id')->constrained('games');
            $table->smallInteger('home_team_score')->nullable();
            $table->smallInteger('away_team_score')->nullable();
            $table->smallInteger('game_winner_id')->nullable();
            $table->timestamp('prediction_date')->nullable();
            $table->binary('generated')->nullable();
            $table->timestamps();
        });

        Schema::create('prediction_standings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('team_id')->constrained('teams');
            $table->tinyInteger('group_position')->nullable();
            $table->tinyInteger('last16')->nullable();
            $table->tinyInteger('quarterfinal')->nullable();
            $table->tinyInteger('semifinal')->nullable();
            $table->tinyInteger('final')->nullable();
            $table->timestamps();
        });

        Schema::create('prediction_survivals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->smallInteger('team_id')->constrained('teams');;
            $table->smallInteger('event_id')->nullable();
            $table->timestamps();
        });

        Schema::create('point_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('game_id')->constrained('games');
            $table->decimal('winner_points');
            $table->decimal('difference_points');
            $table->decimal('bingo_points');
            $table->decimal('odds');
            $table->decimal('odds_points');
            $table->decimal('full_points');
            $table->timestamps();
        });

        Schema::create('point_survivals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('event_id')->constrained('events');
            $table->foreignId('team_id')->constrained('teams');
            $table->smallInteger('survival_points');
            $table->timestamps();
        });

        Schema::create('point_standings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('team_id')->constrained('teams');
            $table->smallInteger('group_position_points')->nullable();
            $table->smallInteger('last16_points')->nullable();
            $table->smallInteger('quarterfinal_points')->nullable();
            $table->smallInteger('semifinal_points')->nullable();
            $table->smallInteger('final_points')->nullable();
            $table->timestamps();
        });

        Schema::create('game_odds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games');
            $table->decimal('home_odds')->nullable();
            $table->decimal('draw_odds')->nullable();
            $table->decimal('away_odds')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logins', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->timestamps();
        });

        Schema::create('audit_prediction_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('game_id')->constrained('games');
            $table->smallInteger('home_team_score')->nullable();
            $table->smallInteger('away_team_score')->nullable();
            $table->smallInteger('game_winner_id')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_prediction_survival', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('event_id')->constrained('events');
            $table->smallInteger('game_winner_id')->nullable();
            $table->timestamps();
        });

        Schema::create('points_calculations', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('home_score_difference');
            $table->smallInteger('away_score_difference');
            $table->smallInteger('points');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('points_calculations');
        Schema::dropIfExists('audit_logins');
        Schema::dropIfExists('audit_prediction_games');
        Schema::dropIfExists('points_standings');
        Schema::dropIfExists('points_survivals');
        Schema::dropIfExists('points_results');
        Schema::dropIfExists('prediction_survivals_teams');
        Schema::dropIfExists('prediction_survivals');
        Schema::dropIfExists('prediction_results');
        Schema::dropIfExists('prediction_standings');
        Schema::dropIfExists('game_odds');
        Schema::dropIfExists('user_settings');
        Schema::dropIfExists('user_groups');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('games');
        Schema::dropIfExists('events');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('groups');

    }
};
