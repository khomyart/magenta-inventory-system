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
        Schema::table('items_prices_warehouses', function (Blueprint $table) {
            $table->after('warehouse_id', function ($table) {
                $table->integer('price_per_item');
                $table->enum('currency', ["UAH", "USD", "EUR"]);
            });

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
