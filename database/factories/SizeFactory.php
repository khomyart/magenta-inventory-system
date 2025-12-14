<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Size>
 */
class SizeFactory extends Factory
{
    public function definition()
    {
        return [
            'value' => $this->faker->randomElement(['XS', 'S', 'M', 'L', 'XL', 'XXL']),
            'description' => $this->faker->words(2, true),
            'number_in_row' => 1,
        ];
    }
}
