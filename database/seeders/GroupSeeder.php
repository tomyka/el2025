<?php

namespace Database\Seeders;

use App\Models\Group;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GroupSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $groups = array(
                    array(
                        'group' => 'Vieša grupė',
                        'group_description' => '',
                        'fee' => '20',
                        'fee_description' => 'Visas mokestis bus skirtas Jaunimo Linijai paremti.',
                        'reward_ratio' => '0.8',
                        'reward_description' => 'Pirmos 5 vietos apdovanojamos Žalgirio suvenyrais su žaidėjų parašais.'
         ));

        DB::table((new Group)->getTable())->insert($groups);

    }
}
