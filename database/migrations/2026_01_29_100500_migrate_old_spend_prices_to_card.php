<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Update existing records: transfer total_price to amount_on_card
        // wherever the breakdown columns are all 0 (indicating old data)
        DB::table('spends')
            ->where('amount_on_card', 0)
            ->where('amount_via_terminal', 0)
            ->where('amount_as_cash', 0)
            ->update(['amount_on_card' => DB::raw('total_price')]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // We can't easily distinguish which ones were migrated vs manually set to card only,
        // but for a strict rollback of this specific action, we might set amount_on_card to 0
        // for records where it equals total_price.
        // However, usually data migrations are destructive/hard to reverse perfectly.
        // We will leave the down method empty or minimal as this is a one-time data fix.
    }
};
