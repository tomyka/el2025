<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_prediction_games', function (Blueprint $table) {
            $table->smallInteger('old_home_team_score')->nullable()->after('game_winner_id');
            $table->smallInteger('old_away_team_score')->nullable()->after('old_home_team_score');
            $table->smallInteger('old_game_winner_id')->nullable()->after('old_away_team_score');
        });
    }

    public function down(): void
    {
        Schema::table('audit_prediction_games', function (Blueprint $table) {
            $table->dropColumn(['old_home_team_score', 'old_away_team_score', 'old_game_winner_id']);
        });
    }
};
