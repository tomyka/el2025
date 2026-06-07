<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->tinyInteger('last32')->nullable()->after('last16');
        });
        Schema::table('prediction_standings', function (Blueprint $table) {
            $table->tinyInteger('last32')->nullable()->after('last16');
        });
        Schema::table('point_standings', function (Blueprint $table) {
            $table->smallInteger('last32_points')->nullable()->after('last16_points');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('last32');
        });
        Schema::table('prediction_standings', function (Blueprint $table) {
            $table->dropColumn('last32');
        });
        Schema::table('point_standings', function (Blueprint $table) {
            $table->dropColumn('last32_points');
        });
    }
};
