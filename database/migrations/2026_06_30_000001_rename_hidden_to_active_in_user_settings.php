<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_settings', function (Blueprint $table) {
            $table->boolean('active')->default(true)->after('receive_reminders');
        });

        DB::statement('UPDATE user_settings SET active = NOT hidden');

        Schema::table('user_settings', function (Blueprint $table) {
            $table->dropColumn('hidden');
        });
    }

    public function down(): void
    {
        Schema::table('user_settings', function (Blueprint $table) {
            $table->boolean('hidden')->default(false)->after('receive_reminders');
        });

        DB::statement('UPDATE user_settings SET hidden = NOT active');

        Schema::table('user_settings', function (Blueprint $table) {
            $table->dropColumn('active');
        });
    }
};
