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
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('involvement_level_1_user_id')->nullable()->after('notes');
            $table->unsignedBigInteger('involvement_level_2_user_id')->nullable()->after('involvement_level_1_user_id');
            $table->unsignedBigInteger('involvement_level_3_user_id')->nullable()->after('involvement_level_2_user_id');

            $table->foreign('involvement_level_1_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('involvement_level_2_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('involvement_level_3_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['involvement_level_1_user_id']);
            $table->dropForeign(['involvement_level_2_user_id']);
            $table->dropForeign(['involvement_level_3_user_id']);

            $table->dropColumn('involvement_level_1_user_id');
            $table->dropColumn('involvement_level_2_user_id');
            $table->dropColumn('involvement_level_3_user_id');
        });
    }
};
