<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::table('allowenses', function (Blueprint $table) {
            $table->string('action_tmp')->nullable()->after('action');
        });

        DB::statement('UPDATE allowenses SET `action_tmp` = `action`');

        Schema::table('allowenses', function (Blueprint $table) {
            $table->dropColumn('action');
            $table->renameColumn('action_tmp', 'action');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('allowenses', function (Blueprint $table) {
            //
        });
    }
};
