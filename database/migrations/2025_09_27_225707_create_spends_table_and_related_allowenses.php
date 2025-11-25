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
        Schema::create('spends', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->decimal('price', 13, 4);
            $table->enum('currency', ['UAH', 'USD', 'EUR'])->default('UAH');
            $table->dateTime('happened_at')->nullable();

            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->foreign('created_by_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->boolean('is_hidden')->default(false);
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
        Schema::dropIfExists('spends');
        $this->removeRelatedAllowenses();
    }

    private function addRelatedAllowenses(): void
    {
        $actions = ['create', 'read', 'update', 'delete', 'hide', 'see_hidden'];
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

    private function removeRelatedAllowenses(): void
    {
        $actions = ['create', 'read', 'update', 'delete', 'hide', 'see_hidden'];
        foreach ($actions as $action) {
            Allowense::query()->where([
                'section' => 'spends',
                'action' => $action,
            ])->first()?->delete();
        }
    }
};
