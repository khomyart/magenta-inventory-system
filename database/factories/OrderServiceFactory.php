<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderService>
 */
class OrderServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'order_id' => Order::factory(),
            'service_id' => Service::factory(),
            'price_per_one_unit' => $this->faker->randomFloat(2, 50, 1000),
            'currency' => 'UAH',
            'quantity' => $this->faker->numberBetween(1, 10),
        ];
    }
}
