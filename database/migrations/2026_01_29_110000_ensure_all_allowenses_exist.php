<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $allowenses = [
            ['types', 'create'], ['types', 'read'], ['types', 'update'], ['types', 'delete'],
            ['sizes', 'create'], ['sizes', 'read'], ['sizes', 'update'], ['sizes', 'delete'],
            ['items', 'create'], ['items', 'read'], ['items', 'update'], ['items', 'delete'],
            ['genders', 'create'], ['genders', 'read'], ['genders', 'update'], ['genders', 'delete'],
            ['colors', 'create'], ['colors', 'read'], ['colors', 'update'], ['colors', 'delete'],
            ['warehouses', 'create'], ['warehouses', 'read'], ['warehouses', 'update'], ['warehouses', 'delete'],
            ['units', 'create'], ['units', 'read'], ['units', 'update'], ['units', 'delete'],
            ['items', 'income'], ['items', 'outcome'], ['items', 'move'],
            ['spends', 'create'], ['spends', 'read'], ['spends', 'update'], ['spends', 'delete'],
            ['spends', 'hide'], ['spends', 'see_hidden'], ['spends', 'update_not_owned'], ['spends', 'delete_not_owned'],
            ['contacts', 'create'], ['contacts', 'read'], ['contacts', 'update'], ['contacts', 'delete'],
            ['services', 'create'], ['services', 'read'], ['services', 'update'], ['services', 'delete'],
            ['orders', 'create'], ['orders', 'read'], ['orders', 'update'], ['orders', 'delete'],
            ['users', 'create'], ['users', 'read'], ['users', 'update'], ['users', 'delete'],
            ['reports', 'read'],
        ];

        foreach ($allowenses as $allowense) {
            $exists = DB::table('allowenses')
                ->where('section', $allowense[0])
                ->where('action', $allowense[1])
                ->exists();

            if (!$exists) {
                DB::table('allowenses')->insert([
                    'section' => $allowense[0],
                    'action' => $allowense[1],
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // We typically don't delete these in down() as they might be used by existing roles,
        // and we can't easily distinguish which ones were added by this specific migration vs strictly manual additions.
        // Leaving empty for safety.
    }
};
