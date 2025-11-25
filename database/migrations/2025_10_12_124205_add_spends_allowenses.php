<?php

use App\Models\Allowense;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $actions = ['update_not_owned', 'delete_not_owned'];
        foreach ($actions as $action) {
            if (! Allowense::query()->where([
                'section' => 'spends',
                'action' => $action,
            ])->exists()) {
                Allowense::create([
                    'section' => 'spends',
                    'action' => $action,
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
        $actions = ['update_not_owned', 'delete_not_owned'];
        foreach ($actions as $action) {
            Allowense::query()->where([
                'section' => 'spends',
                'action' => $action,
            ])->first()?->delete();
        }
    }
};
