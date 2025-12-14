<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    public function definition()
    {
        return [
            'order_id' => Order::factory(),
            'item_id' => Item::factory(),
            'price_per_one_unit' => $this->faker->randomFloat(2, 50, 1000),
            'currency' => 'UAH',
            'quantity' => $this->faker->numberBetween(1, 10),
        ];
    }
}
