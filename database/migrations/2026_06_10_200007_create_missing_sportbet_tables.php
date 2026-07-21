<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_settings')) {
            Schema::create('user_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users');
                $table->tinyInteger('admin');
                $table->smallInteger('result_amount')->nullable();
                $table->smallInteger('time_zone')->nullable();
                $table->timestamps();
            });
        }

        // messages uses league_id (groups table was dropped)
        if (! Schema::hasTable('messages')) {
            Schema::create('messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('league_id')->nullable()->constrained('leagues');
                $table->string('message');
                $table->tinyInteger('active');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('prediction_results')) {
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
        }

        if (! Schema::hasTable('prediction_standings')) {
            Schema::create('prediction_standings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users');
                $table->foreignId('team_id')->constrained('teams');
                $table->tinyInteger('group_position')->nullable();
                $table->tinyInteger('last16')->nullable();
                $table->tinyInteger('last32')->nullable();
                $table->tinyInteger('quarterfinal')->nullable();
                $table->tinyInteger('semifinal')->nullable();
                $table->tinyInteger('final')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('prediction_survivals')) {
            Schema::create('prediction_survivals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users');
                $table->smallInteger('team_id');
                $table->smallInteger('event_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('game_odds')) {
            Schema::create('game_odds', function (Blueprint $table) {
                $table->id();
                $table->foreignId('game_id')->constrained('games');
                $table->decimal('home_odds')->nullable();
                $table->decimal('draw_odds')->nullable();
                $table->decimal('away_odds')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('audit_logins')) {
            Schema::create('audit_logins', function (Blueprint $table) {
                $table->id();
                $table->string('user_id');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('audit_prediction_games')) {
            Schema::create('audit_prediction_games', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users');
                $table->foreignId('game_id')->constrained('games');
                $table->smallInteger('home_team_score')->nullable();
                $table->smallInteger('away_team_score')->nullable();
                $table->smallInteger('game_winner_id')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_prediction_games');
        Schema::dropIfExists('audit_logins');
        Schema::dropIfExists('game_odds');
        Schema::dropIfExists('prediction_survivals');
        Schema::dropIfExists('prediction_standings');
        Schema::dropIfExists('prediction_results');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('user_settings');
    }
};
