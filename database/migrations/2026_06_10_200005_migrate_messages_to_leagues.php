<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // messages table may not exist if the DB is in a partially-migrated state
        if (! Schema::hasTable('messages')) {
            return;
        }

        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        if ($isSqlite) {
            // SQLite cannot drop a column that has a FK constraint defined on it.
            // Recreate the table without group_id, adding league_id with FK to leagues.
            DB::statement('PRAGMA foreign_keys = OFF');

            DB::statement('CREATE TABLE messages_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                league_id INTEGER NULL REFERENCES leagues(id),
                message VARCHAR(255) NOT NULL,
                active TINYINT NOT NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL
            )');

            DB::statement('INSERT INTO messages_new (id, league_id, message, active, created_at, updated_at)
                SELECT id, group_id, message, active, created_at, updated_at FROM messages');

            DB::statement('DROP TABLE messages');
            DB::statement('ALTER TABLE messages_new RENAME TO messages');

            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            Schema::table('messages', function (Blueprint $table) {
                $table->unsignedBigInteger('league_id')->nullable()->after('id');
            });

            // Copy group_id values to league_id (IDs match from the data migration)
            DB::statement('UPDATE messages SET league_id = group_id');

            Schema::table('messages', function (Blueprint $table) {
                $table->foreign('league_id')->references('id')->on('leagues');
                $table->dropForeign(['group_id']);
                $table->dropColumn('group_id');
            });
        }
    }

    public function down(): void
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        if ($isSqlite) {
            DB::statement('PRAGMA foreign_keys = OFF');

            DB::statement('CREATE TABLE messages_old (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                group_id INTEGER NULL REFERENCES groups(id),
                message VARCHAR(255) NOT NULL,
                active TINYINT NOT NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL
            )');

            DB::statement('INSERT INTO messages_old (id, group_id, message, active, created_at, updated_at)
                SELECT id, league_id, message, active, created_at, updated_at FROM messages');

            DB::statement('DROP TABLE messages');
            DB::statement('ALTER TABLE messages_old RENAME TO messages');

            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            Schema::table('messages', function (Blueprint $table) {
                $table->unsignedBigInteger('group_id')->nullable()->after('id');
            });
            DB::statement('UPDATE messages SET group_id = league_id');
            Schema::table('messages', function (Blueprint $table) {
                $table->dropForeign(['league_id']);
                $table->dropColumn('league_id');
            });
        }
    }
};
