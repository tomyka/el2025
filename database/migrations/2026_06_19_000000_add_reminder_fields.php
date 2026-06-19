<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->boolean('reminder_sent')->default(false)->after('away_team_score');
        });

        Schema::table('user_settings', function (Blueprint $table) {
            $table->boolean('receive_reminders')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('reminder_sent');
        });
        Schema::table('user_settings', function (Blueprint $table) {
            $table->dropColumn('receive_reminders');
        });
    }
};
