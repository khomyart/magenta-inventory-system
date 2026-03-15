<?php

use App\Models\Allowense;
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
        Schema::create('business_account_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->decimal('total_price', 13, 4);
            $table->decimal('amount_on_card', 13, 4)->default(0);
            $table->decimal('amount_via_terminal', 13, 4)->default(0);
            $table->decimal('amount_as_cash', 13, 4)->default(0);
            $table->enum('currency', ['UAH', 'USD', 'EUR'])->default('UAH');
            $table->dateTime('happened_at');
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->enum('type', ['income', 'outcome']);
            $table->timestamps();

            $table->foreign('created_by_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });

        $this->addRelatedAllowenses();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->removeRelatedAllowenses();
        Schema::dropIfExists('business_account_transactions');
    }

    private function addRelatedAllowenses(): void
    {
        $actions = ['create', 'read', 'update', 'delete'];
        foreach ($actions as $action) {
            if (! Allowense::query()->where([
                'section' => 'business_account_transactions',
                'action' => $action,
            ])->exists()) {
                Allowense::create([
                    'section' => 'business_account_transactions',
                    'action' => $action,
                ]);
            }
        }
    }

    private function removeRelatedAllowenses(): void
    {
        $actions = ['create', 'read', 'update', 'delete'];
        foreach ($actions as $action) {
            Allowense::query()->where([
                'section' => 'business_account_transactions',
                'action' => $action,
            ])->first()?->delete();
        }
    }
};
