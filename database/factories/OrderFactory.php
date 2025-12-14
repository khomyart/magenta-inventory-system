<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'status' => 'pending',
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => $this->faker->randomFloat(2, 100, 10000),
            'completion_deadline' => null,
            'fully_payed_at' => null,
            'completed_at' => null,
            'contact_id' => null,
            'warehouse_id' => null,
            'notes' => null,
            'involvement_level_1_user_id' => null,
            'involvement_level_2_user_id' => null,
            'involvement_level_3_user_id' => null,
        ];
    }
}
