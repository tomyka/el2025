<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('league_game_odds', function (Blueprint $table) {
            $table->foreignId('league_id')->constrained('leagues')->cascadeOnDelete();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->decimal('home_odds', 8, 2)->default(1.0);
            $table->decimal('draw_odds', 8, 2)->default(1.0);
            $table->decimal('away_odds', 8, 2)->default(1.0);
            $table->timestamp('updated_at')->nullable();
            $table->primary(['league_id', 'game_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('league_game_odds');
    }
};
