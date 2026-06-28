<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('point_standings', function (Blueprint $table) {
            $table->float('group_position_points')->nullable()->change();
            $table->float('group_position_odds')->nullable()->after('group_position_points');
            $table->float('last32_points')->nullable()->change();
            $table->float('last32_odds')->nullable()->after('last32_points');
            $table->float('last16_points')->nullable()->change();
            $table->float('last16_odds')->nullable()->after('last16_points');
            $table->float('quarterfinal_points')->nullable()->change();
            $table->float('quarterfinal_odds')->nullable()->after('quarterfinal_points');
            $table->float('semifinal_points')->nullable()->change();
            $table->float('semifinal_odds')->nullable()->after('semifinal_points');
            $table->float('final_points')->nullable()->change();
            $table->float('final_odds')->nullable()->after('final_points');
        });
    }

    public function down(): void
    {
        Schema::table('point_standings', function (Blueprint $table) {
            $table->dropColumn(['group_position_odds', 'last32_odds', 'last16_odds', 'quarterfinal_odds', 'semifinal_odds', 'final_odds']);
            $table->smallInteger('group_position_points')->nullable()->change();
            $table->smallInteger('last32_points')->nullable()->change();
            $table->smallInteger('last16_points')->nullable()->change();
            $table->smallInteger('quarterfinal_points')->nullable()->change();
            $table->smallInteger('semifinal_points')->nullable()->change();
            $table->smallInteger('final_points')->nullable()->change();
        });
    }
};
