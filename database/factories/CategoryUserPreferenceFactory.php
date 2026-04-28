<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\CategoryUserPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoryUserPreference>
 */
class CategoryUserPreferenceFactory extends Factory
{
    protected $model = CategoryUserPreference::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'is_hidden' => fake()->boolean(20),
            'display_order_override' => fake()->optional()->numberBetween(1, 200),
        ];
    }
}
