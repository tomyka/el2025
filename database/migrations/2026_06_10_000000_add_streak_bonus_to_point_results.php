<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('point_results', 'streak_bonus')) {
            Schema::table('point_results', function (Blueprint $table) {
                $table->decimal('streak_bonus', 8, 2)->default(0)->after('full_points');
            });
        }
    }

    public function down(): void
    {
        Schema::table('point_results', function (Blueprint $table) {
            $table->dropColumn('streak_bonus');
        });
    }
};
