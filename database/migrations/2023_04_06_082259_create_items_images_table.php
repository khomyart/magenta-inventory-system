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
        Schema::create('items_images', function (Blueprint $table) {
            $table->id();

            $table->foreignId("item_id");
            $table
                ->foreign("item_id")
                ->references("id")->on("items")
                ->onUpdate("cascade")->onDelete("cascade");

            $table->foreignId("image_id");
            $table
                ->foreign("image_id")
                ->references("id")->on("images")
                ->onUpdate("cascade")->onDelete("cascade");

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
        Schema::dropIfExists('items_images');
    }
};
