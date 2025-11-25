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
        $this->addRelatedAllowenses();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->removeRelatedAllowenses();
    }

    private function addRelatedAllowenses(): void
    {
        $actions = ['create', 'read', 'update', 'delete'];
        foreach ($actions as $action) {
            if (! Allowense::query()->where([
                'section' => 'users',
                'action' => $action,
            ])->exists()) {
                Allowense::create([
                    'section' => 'users',
                    'action' => $action,
                ]);
            }
        }
    }

    private function removeRelatedAllowenses(): void
    {
        $actions = ['create', 'read', 'update', 'delete'];
        foreach ($actions as $action) {
            Allowense::query()->where([
                'section' => 'users',
                'action' => $action,
            ])->first()?->delete();
        }
    }
};
