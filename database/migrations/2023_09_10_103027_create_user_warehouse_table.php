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
        Schema::create('user_warehouse', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId("user_id");
            $table
                ->foreign("user_id")
                ->references("id")->on("users")
                ->onUpdate("cascade")->onDelete("cascade");
            $table->foreignId("warehouse_id");
            $table
                ->foreign("warehouse_id")
                ->references("id")->on("warehouses")
                ->onUpdate("cascade")->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_warehouses');
    }
};
