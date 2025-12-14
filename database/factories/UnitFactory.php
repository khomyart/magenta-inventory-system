<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Unit>
 */
class UnitFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->randomElement(['шт', 'кг', 'м', 'л', 'г']),
            'description' => $this->faker->words(2, true),
        ];
    }
}
