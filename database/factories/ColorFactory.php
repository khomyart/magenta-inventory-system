<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Color>
 */
class ColorFactory extends Factory
{
    public function definition()
    {
        return [
            'value' => $this->faker->hexColor(),
            'article' => $this->faker->unique()->numerify('CLR-####'),
            'description' => $this->faker->safeColorName(),
            'text_color_value' => '#000000',
        ];
    }
}
