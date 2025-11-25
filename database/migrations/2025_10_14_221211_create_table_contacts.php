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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('phone', 20);
            $table->string('email', 150)->nullable();
            $table->string('address')->nullable();
            $table->json('preferred_platforms');
            $table->timestamps();
        });
        $this->addRelatedAllowenses();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contacts');
        $this->removeRelatedAllowenses();
    }

    private function addRelatedAllowenses(): void
    {
        $actions = ['create', 'read', 'update', 'delete'];
        foreach ($actions as $action) {
            if (! Allowense::query()->where([
                'section' => 'contacts',
                'action' => $action,
            ])->exists()) {
                Allowense::create([
                    'section' => 'contacts',
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
                'section' => 'contacts',
                'action' => $action,
            ])->first()?->delete();
        }
    }
};
