<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sport')->default('football');
            $table->enum('status', ['upcoming', 'active', 'finished'])->default('upcoming');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('survival_game')->default(false);
            $table->timestamps();
        });

        // Seed World Football Cup 2026 as tournament 1
        $survivalVal = (bool) DB::table('settings')
            ->where('setting', 'survivalGame')
            ->value('value');

        DB::table('tournaments')->insert([
            'name'          => 'World Football Cup 2026',
            'slug'          => 'world-cup-2026',
            'sport'         => 'football',
            'status'        => 'active',
            'is_public'     => true,
            'survival_game' => $survivalVal,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // Add nullable tournament_id to events, teams, leagues
        foreach (['events', 'teams', 'leagues'] as $tbl) {
            Schema::table($tbl, function (Blueprint $table) {
                $table->unsignedBigInteger('tournament_id')->nullable()->after('id');
                $table->foreign('tournament_id')->references('id')->on('tournaments');
            });
        }

        // Backfill all existing rows → tournament 1
        DB::table('events')->update(['tournament_id' => 1]);
        DB::table('teams')->update(['tournament_id' => 1]);
        DB::table('leagues')->update(['tournament_id' => 1]);

        // Make NOT NULL with default 1 now that all rows are filled
        foreach (['events', 'teams', 'leagues'] as $tbl) {
            Schema::table($tbl, function (Blueprint $table) {
                $table->unsignedBigInteger('tournament_id')->nullable(false)->default(1)->change();
            });
        }
    }

    public function down(): void
    {
        foreach (['events', 'teams', 'leagues'] as $tbl) {
            Schema::table($tbl, function (Blueprint $table) {
                $table->dropForeign(['tournament_id']);
                $table->dropColumn('tournament_id');
            });
        }
        Schema::dropIfExists('tournaments');
    }
};
