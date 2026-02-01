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
        Schema::table('spends', function (Blueprint $table) {
            $table->renameColumn('price', 'total_price');
            $table->decimal('amount_on_card', 13, 4)->default(0)->after('price');
            $table->decimal('amount_via_terminal', 13, 4)->default(0)->after('amount_on_card');
            $table->decimal('amount_as_cash', 13, 4)->default(0)->after('amount_via_terminal');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('spends', function (Blueprint $table) {
            $table->dropColumn(['amount_on_card', 'amount_via_terminal', 'amount_as_cash']);
            $table->renameColumn('total_price', 'price');
        });
    }
};
