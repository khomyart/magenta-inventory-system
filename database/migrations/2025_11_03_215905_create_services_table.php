<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Allowense;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->decimal('price', 13, 4);
            $table->enum('currency', ['UAH', 'USD', 'EUR'])->default('UAH');
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
        Schema::dropIfExists('services');
        $this->removeRelatedAllowenses();
    }

    private function addRelatedAllowenses(): void
    {
        $actions = ['create', 'read', 'update', 'delete'];
        foreach ($actions as $action) {
            if (!Allowense::query()->where([
                'section' => 'services',
                'action' => $action,
            ])->exists()) {
                Allowense::create([
                    'section' => 'services',
                    'action' => $action,
                ]);
            }
        }
    }

    private function removeRelatedAllowenses(): void
    {
        Allowense::query()->where('section', 'services')->delete();
    }
};
