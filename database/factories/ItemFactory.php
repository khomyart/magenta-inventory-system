<?php

namespace Database\Factories;

use App\Models\Type;
use App\Models\Color;
use App\Models\Size;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
{
    public function definition()
    {
        return [
            'group_id' => $this->faker->uuid(),
            'article' => $this->faker->unique()->numerify('ART-####'),
            'title' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'type_id' => Type::factory(),
            'gender_id' => null,
            'size_id' => null,
            'color_id' => null,
            'unit_id' => Unit::factory(),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'lack' => 0,
            'currency' => 'UAH',
        ];
    }
}
