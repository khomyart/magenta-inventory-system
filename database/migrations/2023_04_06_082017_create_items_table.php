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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string("article", 10);
            $table->string("title", 255);

            $table->foreignId("type_id");
            $table
                ->foreign("type_id")
                ->references("id")->on("types")
                ->onUpdate("cascade")->onDelete("cascade");

            $table->foreignId("gender_id")->nullable();
            $table
                ->foreign("gender_id")
                ->references("id")->on("genders")
                ->onUpdate("cascade")->onDelete("cascade");

            $table->foreignId("size_id")->nullable();
            $table
                ->foreign("size_id")
                ->references("id")->on("sizes")
                ->onUpdate("cascade")->onDelete("cascade");

            $table->foreignId("color_id")->nullable();
            $table
                ->foreign("color_id")
                ->references("id")->on("colors")
                ->onUpdate("cascade")->onDelete("cascade");

            $table->foreignId("unit_id");
            $table
                ->foreign("unit_id")
                ->references("id")->on("units")
                ->onUpdate("cascade")->onDelete("cascade");

            $table->integer("price");

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
        Schema::dropIfExists('items');
    }
};
