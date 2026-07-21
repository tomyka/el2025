<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * leagues.tournament_id defaulted to 1, which silently misattributed any
 * League created without an explicit tournament_id to whatever tournament
 * happened to be id 1 (see PostRegisterController/LeagueController fixes in
 * the same change). Every real creation path now passes tournament_id
 * explicitly, so drop the default: a future oversight should fail loudly
 * (NOT NULL violation) instead of silently joining the wrong tournament.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->unsignedBigInteger('tournament_id')->nullable(false)->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->unsignedBigInteger('tournament_id')->nullable(false)->default(1)->change();
        });
    }
};
