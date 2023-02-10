<?php

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
        Schema::create('roles_allowenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId("role_id");
            $table->foreign("role_id")
                ->references("id")
                ->on("roles")
                ->onUpdate("cascade")
                ->onDelete("cascade");
            $table->foreignId("allowense_id");
            $table->foreign("allowense_id")
                ->references("id")
                ->on("allowenses")
                ->onUpdate("cascade")
                ->onDelete("cascade");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('roles_allowenses');
    }
};
