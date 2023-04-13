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
        Schema::create('items_warehouses', function (Blueprint $table) {
            $table->id();

            $table->foreignId("item_id");
            $table
                ->foreign("item_id")
                ->references("id")->on("items")
                ->onUpdate("cascade")->onDelete("cascade");

            $table->foreignId("warehouse_id");
            $table
                ->foreign("warehouse_id")
                ->references("id")->on("warehouses")
                ->onUpdate("cascade")->onDelete("cascade");

            $table->integer("amount_of_items");
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
        Schema::dropIfExists('items_warehouses');
    }
};
