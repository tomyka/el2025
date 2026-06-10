<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Migrate existing groups → leagues with identical IDs
        $groups = DB::table('groups')->get();
        foreach ($groups as $group) {
            DB::table('leagues')->insertOrIgnore([
                'id'                 => $group->id,
                'name'               => $group->group,
                'description'        => $group->group_description,
                'is_public'          => false,
                'owner_id'           => null,
                'base_fee'           => $group->fee,
                'penalty_step'       => null,
                'use_league_odds'    => false,
                'reward_description' => $group->reward_description,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }

        // 2. Create the public league (gets next auto-increment ID), or use existing
        $existing = DB::table('leagues')->where('is_public', true)->first();
        if ($existing) {
            $publicLeagueId = $existing->id;
        } else {
            $publicLeagueId = DB::table('leagues')->insertGetId([
                'name'       => 'Public League',
                'is_public'  => true,
                'owner_id'   => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Migrate user_groups → league_members (table may not exist if already dropped)
        $userGroups = Schema::hasTable('user_groups')
            ? DB::table('user_groups')->get()
            : collect();
        foreach ($userGroups as $ug) {
            DB::table('league_members')->insertOrIgnore([
                'league_id'  => $ug->group_id,
                'user_id'    => $ug->user_id,
                'is_admin'   => false,
                'is_guest'   => (bool) $ug->guest,
                'active'     => (bool) $ug->active,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 4. Collect users already migrated into league_members
        $memberedUserIds = DB::table('league_members')->pluck('user_id')->unique();

        // 5. Add all existing members to public league (non-active)
        foreach ($memberedUserIds as $uid) {
            DB::table('league_members')->insertOrIgnore([
                'league_id'  => $publicLeagueId,
                'user_id'    => $uid,
                'is_admin'   => false,
                'is_guest'   => false,
                'active'     => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 6. Auto-join users with NO existing membership to public league as active
        $allUserIds = DB::table('users')->pluck('id');
        foreach ($allUserIds as $uid) {
            if (!$memberedUserIds->contains($uid)) {
                DB::table('league_members')->insertOrIgnore([
                    'league_id'  => $publicLeagueId,
                    'user_id'    => $uid,
                    'is_admin'   => false,
                    'is_guest'   => false,
                    'active'     => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('league_members')->delete();
        DB::table('leagues')->delete();
    }
};
