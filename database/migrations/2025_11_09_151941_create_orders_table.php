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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->decimal('amount_of_advance_payment_on_card', 13, 4)->default(0);
            $table->decimal('amount_of_advance_payment_via_terminal', 13, 4)->default(0);
            $table->decimal('amount_of_advance_payment_as_cash', 13, 4)->default(0);
            $table->decimal('amount_of_final_payment_on_card', 13, 4)->default(0);
            $table->decimal('amount_of_final_payment_via_terminal', 13, 4)->default(0);
            $table->decimal('amount_of_final_payment_as_cash', 13, 4)->default(0);
            $table->enum('currency', ['UAH', 'USD', 'EUR'])->default('UAH');
            $table->decimal('discount', 13, 4)->default(0);
            $table->decimal('total_price', 13, 4)->default(0);
            $table->timestamp('completion_deadline')->nullable();
            $table->timestamp('fully_payed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->onDelete('set null');
            $table->timestamps();
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
        Schema::dropIfExists('orders');
        $this->removeRelatedAllowenses();
    }

    private function addRelatedAllowenses(): void
    {
        $actions = ['create', 'read', 'update', 'delete'];
        foreach ($actions as $action) {
            if (! Allowense::query()->where([
                'section' => 'orders',
                'action' => $action,
            ])->exists()) {
                Allowense::create([
                    'section' => 'orders',
                    'action' => $action,
                ]);
            }
        }
    }

    private function removeRelatedAllowenses(): void
    {
        Allowense::query()->where('section', 'orders')->delete();
    }
};
