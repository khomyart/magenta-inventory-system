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
        Schema::table('images', function(Blueprint $table) {
            $table->foreignId('item_id')->after('id');
            $table
                ->foreign('item_id')->references('id')->on('items')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->tinyInteger('number_in_row')->after('src');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
