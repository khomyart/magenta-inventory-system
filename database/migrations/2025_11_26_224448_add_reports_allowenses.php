<?php

use App\Models\Allowense;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
        $actions = ['read'];
        foreach ($actions as $action) {
            if (! Allowense::query()->where([
                'section' => 'reports',
                'action' => $action,
            ])->exists()) {
                Allowense::create([
                    'section' => 'reports',
                    'action' => $action,
                ]);
            }
        }
    }

    private function removeRelatedAllowenses(): void
    {
        Allowense::query()->where('section', 'reports')->delete();
    }
};
